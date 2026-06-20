<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\AuthenticationRateLimitListener;
use App\Security\AuthenticationRateLimiter;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\RateLimiter\Reservation;

final class AuthenticationRateLimitListenerTest extends TestCase
{
    /**
     * @param non-empty-string $path
     * @param non-empty-string $expectedMethod
     */
    #[DataProvider('targetPostPathProvider')]
    public function testTargetPostPathsConsumeOnlyExpectedLimiter(string $path, string $expectedMethod): void
    {
        $request = Request::create($path, 'POST');
        $context = $this->createRateLimiterContext();

        (new AuthenticationRateLimitListener($context->rateLimiter))(
            $this->createRequestEvent($request, HttpKernelInterface::MAIN_REQUEST)
        );

        foreach ($context->limitersByMethod as $method => $limiter) {
            self::assertSame(
                $method === $expectedMethod ? 1 : 0,
                $limiter->consumeCalls,
                sprintf('Unexpected consume count for %s.', $method),
            );
        }

        $expectedFactory = $context->factoriesByMethod[$expectedMethod];
        self::assertNotNull($expectedFactory->lastKey);
        self::assertSame(
            hash_hmac('sha256', $this->scopeForMethod($expectedMethod) . "\0" . $request->getClientIp(), 'listener-secret'),
            $expectedFactory->lastKey,
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function targetPostPathProvider(): iterable
    {
        yield 'login' => ['/api/login/', 'consumeLoginIp'];
        yield 'refresh' => ['/api/refresh/', 'consumeRefreshIp'];
        yield 'activation' => ['/api/clients/activate/', 'consumeActivationIp'];
        yield 'registration' => ['/api/client/registration/', 'consumeRegistrationIp'];
    }

    public function testSubrequestIsIgnored(): void
    {
        $context = $this->createRateLimiterContext();

        (new AuthenticationRateLimitListener($context->rateLimiter))(
            $this->createRequestEvent(Request::create('/api/login/', 'POST'), HttpKernelInterface::SUB_REQUEST)
        );

        $this->assertNoConsumption($context);
    }

    public function testNonPostRequestIsIgnored(): void
    {
        $context = $this->createRateLimiterContext();

        (new AuthenticationRateLimitListener($context->rateLimiter))(
            $this->createRequestEvent(Request::create('/api/login/', 'GET'), HttpKernelInterface::MAIN_REQUEST)
        );

        $this->assertNoConsumption($context);
    }

    public function testUnrelatedPostRouteIsIgnored(): void
    {
        $context = $this->createRateLimiterContext();

        (new AuthenticationRateLimitListener($context->rateLimiter))(
            $this->createRequestEvent(Request::create('/api/something-else/', 'POST'), HttpKernelInterface::MAIN_REQUEST)
        );

        $this->assertNoConsumption($context);
    }

    private function createRateLimiterContext(): ListenerRateLimiterContext
    {
        $loginIdentityFactory = new ListenerRecordingRateLimiterFactory($this->createLimiter());
        $loginIpLimiter = $this->createLimiter();
        $refreshIpLimiter = $this->createLimiter();
        $activationIpLimiter = $this->createLimiter();
        $registrationIpLimiter = $this->createLimiter();

        $factoriesByMethod = [
            'consumeLoginIp' => new ListenerRecordingRateLimiterFactory($loginIpLimiter),
            'consumeRefreshIp' => new ListenerRecordingRateLimiterFactory($refreshIpLimiter),
            'consumeActivationIp' => new ListenerRecordingRateLimiterFactory($activationIpLimiter),
            'consumeRegistrationIp' => new ListenerRecordingRateLimiterFactory($registrationIpLimiter),
        ];

        return new ListenerRateLimiterContext(
            new AuthenticationRateLimiter(
                $loginIdentityFactory,
                $factoriesByMethod['consumeLoginIp'],
                $factoriesByMethod['consumeRefreshIp'],
                $factoriesByMethod['consumeActivationIp'],
                $factoriesByMethod['consumeRegistrationIp'],
                'listener-secret',
            ),
            [
                'consumeLoginIp' => $loginIpLimiter,
                'consumeRefreshIp' => $refreshIpLimiter,
                'consumeActivationIp' => $activationIpLimiter,
                'consumeRegistrationIp' => $registrationIpLimiter,
            ],
            $factoriesByMethod,
        );
    }

    private function createLimiter(): ListenerRecordingLimiter
    {
        return new ListenerRecordingLimiter(new RateLimit(1, new DateTimeImmutable('+1 minute'), true, 1));
    }

    private function assertNoConsumption(ListenerRateLimiterContext $context): void
    {
        foreach ($context->limitersByMethod as $method => $limiter) {
            self::assertSame(0, $limiter->consumeCalls, sprintf('Unexpected consume count for %s.', $method));
        }
    }

    private function scopeForMethod(string $method): string
    {
        return match ($method) {
            'consumeLoginIp' => 'login_ip',
            'consumeRefreshIp' => 'refresh_ip',
            'consumeActivationIp' => 'activation_ip',
            'consumeRegistrationIp' => 'registration_ip',
        };
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

final readonly class ListenerRateLimiterContext
{
    /**
     * @param array<string, ListenerRecordingLimiter> $limitersByMethod
     * @param array<string, ListenerRecordingRateLimiterFactory> $factoriesByMethod
     */
    public function __construct(
        public AuthenticationRateLimiter $rateLimiter,
        public array $limitersByMethod,
        public array $factoriesByMethod,
    ) {}
}

final class ListenerRecordingRateLimiterFactory implements RateLimiterFactoryInterface
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

final class ListenerRecordingLimiter implements LimiterInterface
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
