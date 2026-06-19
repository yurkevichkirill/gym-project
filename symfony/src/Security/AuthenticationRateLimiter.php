<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final readonly class AuthenticationRateLimiter
{
    public function __construct(
        #[Target('auth_login_identity')]
        private RateLimiterFactoryInterface $loginIdentityLimiter,

        #[Target('auth_login_ip')]
        private RateLimiterFactoryInterface $loginIpLimiter,

        #[Target('auth_refresh_ip')]
        private RateLimiterFactoryInterface $refreshIpLimiter,

        #[Target('auth_activation_ip')]
        private RateLimiterFactoryInterface $activationIpLimiter,

        #[Target('auth_registration_ip')]
        private RateLimiterFactoryInterface $registrationIpLimiter,

        private string $secret,
    ) {}

    public function consumeLoginIp(Request $request): void
    {
        $this->consumeIp(
            factory: $this->loginIpLimiter,
            request: $request,
            scope: 'login_ip',
        );
    }

    public function consumeLoginIdentity(Request $request, string $email): void
    {
        $key = $this->createKey(
            'login_identity',
            $this->getClientIp($request),
            strtolower(trim($email)),
        );

        $this->assertAccepted(
            $this->loginIdentityLimiter->create($key)->consume(),
        );
    }

    public function resetLoginIdentity(Request $request, string $email): void
    {
        $key = $this->createKey(
            'login_identity',
            $this->getClientIp($request),
            strtolower(trim($email)),
        );

        $this->loginIdentityLimiter->create($key)->reset();
    }

    public function consumeRefreshIp(Request $request): void
    {
        $this->consumeIp(
            factory: $this->refreshIpLimiter,
            request: $request,
            scope: 'refresh_ip',
        );
    }

    public function consumeActivationIp(Request $request): void
    {
        $this->consumeIp(
            factory: $this->activationIpLimiter,
            request: $request,
            scope: 'activation_ip',
        );
    }

    public function consumeRegistrationIp(Request $request): void
    {
        $this->consumeIp(
            factory: $this->registrationIpLimiter,
            request: $request,
            scope: 'registration_ip',
        );
    }

    private function consumeIp(
        RateLimiterFactoryInterface $factory,
        Request $request,
        string $scope,
    ): void {
        $key = $this->createKey(
            $scope,
            $this->getClientIp($request),
        );

        $this->assertAccepted(
            $factory->create($key)->consume(),
        );
    }

    private function assertAccepted(RateLimit ...$limits): void
    {
        $rejected = false;
        $retryAfter = 1;

        foreach ($limits as $limit) {
            if ($limit->isAccepted()) {
                continue;
            }

            $rejected = true;
            $retryAfter = max(
                $retryAfter,
                $limit->getRetryAfter()->getTimestamp() - time(),
            );
        }

        if (!$rejected) {
            return;
        }

        throw new TooManyRequestsHttpException(
            retryAfter: max(1, $retryAfter),
            message: 'Too many requests. Try again later.',
        );
    }

    private function getClientIp(Request $request): string
    {
        return $request->getClientIp() ?? 'unknown';
    }

    private function createKey(string $scope, string ...$parts): string
    {
        return hash_hmac(
            'sha256',
            $scope . "\0" . implode("\0", $parts),
            $this->secret,
        );
    }
}
