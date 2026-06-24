<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Contact\Security\ContactRateLimiter;
use App\EventListener\ContactRateLimitListener;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\RateLimiter\Reservation;

final class ContactRateLimitListenerTest extends TestCase
{
    public function testContactPostConsumesIpLimiter(): void
    {
        $limiter = new ContactRecordingLimiter(
            new RateLimit(4, new DateTimeImmutable('+15 minutes'), true, 5),
        );
        $factory = new ContactRecordingRateLimiterFactory($limiter);
        $rateLimiter = new ContactRateLimiter($factory, 'contact-secret');
        $request = Request::create(
            uri: '/api/contact/',
            method: Request::METHOD_POST,
            server: ['REMOTE_ADDR' => '203.0.113.10'],
        );

        (new ContactRateLimitListener($rateLimiter))(
            $this->createRequestEvent($request, HttpKernelInterface::MAIN_REQUEST),
        );

        self::assertSame(1, $limiter->consumeCalls);
        self::assertSame(
            hash_hmac('sha256', "contact_form_ip\0" . '203.0.113.10', 'contact-secret'),
            $factory->lastKey,
        );
    }

    /**
     * @param non-empty-string $path
     * @param non-empty-string $method
     */
    #[DataProvider('ignoredRequestProvider')]
    public function testNonTargetRequestsAreIgnored(string $path, string $method, int $requestType): void
    {
        $limiter = new ContactRecordingLimiter(
            new RateLimit(4, new DateTimeImmutable('+15 minutes'), true, 5),
        );
        $factory = new ContactRecordingRateLimiterFactory($limiter);
        $rateLimiter = new ContactRateLimiter($factory, 'contact-secret');

        (new ContactRateLimitListener($rateLimiter))(
            $this->createRequestEvent(Request::create($path, $method), $requestType),
        );

        self::assertSame(0, $limiter->consumeCalls);
        self::assertNull($factory->lastKey);
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function ignoredRequestProvider(): iterable
    {
        yield 'GET request' => ['/api/contact/', Request::METHOD_GET, HttpKernelInterface::MAIN_REQUEST];
        yield 'different POST route' => ['/api/other/', Request::METHOD_POST, HttpKernelInterface::MAIN_REQUEST];
        yield 'subrequest' => ['/api/contact/', Request::METHOD_POST, HttpKernelInterface::SUB_REQUEST];
    }

    public function testRejectedLimitThrowsTooManyRequests(): void
    {
        $limiter = new ContactRecordingLimiter(
            new RateLimit(0, new DateTimeImmutable('+1 minute'), false, 5),
        );
        $rateLimiter = new ContactRateLimiter(
            new ContactRecordingRateLimiterFactory($limiter),
            'contact-secret',
        );

        try {
            $rateLimiter->consume(Request::create('/api/contact/', Request::METHOD_POST));
            self::fail('Expected contact rate limit rejection.');
        } catch (TooManyRequestsHttpException $exception) {
            self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $exception->getStatusCode());
            self::assertSame('Too many contact requests. Try again later.', $exception->getMessage());
            self::assertArrayHasKey('Retry-After', $exception->getHeaders());
        }
    }

    private function createRequestEvent(Request $request, int $requestType): RequestEvent
    {
        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };

        return new RequestEvent($kernel, $request, $requestType);
    }
}

final class ContactRecordingRateLimiterFactory implements RateLimiterFactoryInterface
{
    public ?string $lastKey = null;

    public function __construct(
        private readonly LimiterInterface $limiter,
    ) {}

    public function create(?string $key = null): LimiterInterface
    {
        $this->lastKey = $key;

        return $this->limiter;
    }
}

final class ContactRecordingLimiter implements LimiterInterface
{
    public int $consumeCalls = 0;

    public function __construct(
        private readonly RateLimit $rateLimit,
    ) {}

    public function reserve(int $tokens = 1, ?float $maxTime = null): Reservation
    {
        throw new \BadMethodCallException('reserve() is not used by these tests.');
    }

    public function consume(int $tokens = 1): RateLimit
    {
        ++$this->consumeCalls;

        return $this->rateLimit;
    }

    public function reset(): void
    {
    }
}
