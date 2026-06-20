<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\AuthenticationRateLimiter;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\RateLimiter\Reservation;

final class AuthenticationRateLimiterTest extends TestCase
{
    private const string SECRET = 'test-secret';

    public function testConsumeLoginIdentityNormalizesEmailBuildsExpectedHmacKeyAndConsumes(): void
    {
        $limiter = new RecordingLimiter(new RateLimit(9, new DateTimeImmutable('+1 minute'), true, 10));
        $factory = new RecordingRateLimiterFactory($limiter);
        $rateLimiter = $this->createRateLimiter(loginIdentityFactory: $factory);

        $rateLimiter->consumeLoginIdentity('  USER@Example.COM  ');

        self::assertSame($this->expectedKey('login_identity', 'user@example.com'), $factory->lastKey);
        self::assertSame(1, $limiter->consumeCalls);
        self::assertSame(1, $limiter->lastConsumedTokens);
    }

    public function testResetLoginIdentityUsesSameNormalizedKeyAndResets(): void
    {
        $limiter = new RecordingLimiter(new RateLimit(9, new DateTimeImmutable('+1 minute'), true, 10));
        $factory = new RecordingRateLimiterFactory($limiter);
        $rateLimiter = $this->createRateLimiter(loginIdentityFactory: $factory);

        $rateLimiter->resetLoginIdentity("\tUSER@Example.COM\n");

        self::assertSame($this->expectedKey('login_identity', 'user@example.com'), $factory->lastKey);
        self::assertSame(1, $limiter->resetCalls);
        self::assertSame(0, $limiter->consumeCalls);
    }

    public function testConsumeLoginIpUsesRequestClientIp(): void
    {
        $limiter = new RecordingLimiter(new RateLimit(99, new DateTimeImmutable('+1 minute'), true, 100));
        $factory = new RecordingRateLimiterFactory($limiter);
        $rateLimiter = $this->createRateLimiter(loginIpFactory: $factory);
        $request = Request::create('/api/login/', 'POST', server: ['REMOTE_ADDR' => '203.0.113.10']);

        $rateLimiter->consumeLoginIp($request);

        self::assertSame($this->expectedKey('login_ip', '203.0.113.10'), $factory->lastKey);
        self::assertSame(1, $limiter->consumeCalls);
    }

    public function testConsumeLoginIpUsesUnknownWhenRequestHasNoClientIp(): void
    {
        $limiter = new RecordingLimiter(new RateLimit(99, new DateTimeImmutable('+1 minute'), true, 100));
        $factory = new RecordingRateLimiterFactory($limiter);
        $rateLimiter = $this->createRateLimiter(loginIpFactory: $factory);

        $rateLimiter->consumeLoginIp(new Request());

        self::assertSame($this->expectedKey('login_ip', 'unknown'), $factory->lastKey);
        self::assertSame(1, $limiter->consumeCalls);
    }

    public function testRejectedRateLimitThrowsTooManyRequestsWithRetryAfter(): void
    {
        $limiter = new RecordingLimiter(new RateLimit(0, new DateTimeImmutable('+30 seconds'), false, 10));
        $rateLimiter = $this->createRateLimiter(loginIdentityFactory: new RecordingRateLimiterFactory($limiter));

        try {
            $rateLimiter->consumeLoginIdentity('blocked@example.com');
            self::fail('Expected TooManyRequestsHttpException to be thrown.');
        } catch (TooManyRequestsHttpException $exception) {
            self::assertSame(429, $exception->getStatusCode());
            self::assertSame('Too many requests. Try again later.', $exception->getMessage());

            $headers = $exception->getHeaders();
            self::assertArrayHasKey('Retry-After', $headers);
            self::assertIsNumeric($headers['Retry-After']);
            self::assertGreaterThanOrEqual(1, (int) $headers['Retry-After']);
        }
    }

    public function testAcceptedRateLimitDoesNotThrow(): void
    {
        $limiter = new RecordingLimiter(new RateLimit(1, new DateTimeImmutable('+1 minute'), true, 10));
        $rateLimiter = $this->createRateLimiter(loginIdentityFactory: new RecordingRateLimiterFactory($limiter));

        $rateLimiter->consumeLoginIdentity('allowed@example.com');

        self::assertSame(1, $limiter->consumeCalls);
    }

    private function createRateLimiter(
        ?RateLimiterFactoryInterface $loginIdentityFactory = null,
        ?RateLimiterFactoryInterface $loginIpFactory = null,
        ?RateLimiterFactoryInterface $refreshIpFactory = null,
        ?RateLimiterFactoryInterface $activationIpFactory = null,
        ?RateLimiterFactoryInterface $registrationIpFactory = null,
    ): AuthenticationRateLimiter {
        $acceptedLimiter = new RecordingLimiter(new RateLimit(1, new DateTimeImmutable('+1 minute'), true, 1));
        $fallbackFactory = new RecordingRateLimiterFactory($acceptedLimiter);

        return new AuthenticationRateLimiter(
            $loginIdentityFactory ?? $fallbackFactory,
            $loginIpFactory ?? $fallbackFactory,
            $refreshIpFactory ?? $fallbackFactory,
            $activationIpFactory ?? $fallbackFactory,
            $registrationIpFactory ?? $fallbackFactory,
            self::SECRET,
        );
    }

    private function expectedKey(string $scope, string ...$parts): string
    {
        return hash_hmac('sha256', $scope . "\0" . implode("\0", $parts), self::SECRET);
    }
}

final class RecordingRateLimiterFactory implements RateLimiterFactoryInterface
{
    public ?string $lastKey = null;

    public int $createCalls = 0;

    public function __construct(
        private readonly LimiterInterface $limiter,
    ) {}

    public function create(?string $key = null): LimiterInterface
    {
        $this->lastKey = $key;
        ++$this->createCalls;

        return $this->limiter;
    }
}

final class RecordingLimiter implements LimiterInterface
{
    public int $consumeCalls = 0;

    public int $resetCalls = 0;

    public ?int $lastConsumedTokens = null;

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
        $this->lastConsumedTokens = $tokens;

        return $this->rateLimit;
    }

    public function reset(): void
    {
        ++$this->resetCalls;
    }
}
