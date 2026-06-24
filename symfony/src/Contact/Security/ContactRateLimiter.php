<?php

declare(strict_types=1);

namespace App\Contact\Security;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final readonly class ContactRateLimiter
{
    public function __construct(
        #[Target('contact_form_ip')]
        private RateLimiterFactoryInterface $limiter,
        private string $secret,
    ) {}

    public function consume(Request $request): void
    {
        $clientIp = $request->getClientIp() ?? 'unknown';
        $key = hash_hmac('sha256', "contact_form_ip\0" . $clientIp, $this->secret);
        $limit = $this->limiter->create($key)->consume();

        if ($limit->isAccepted()) {
            return;
        }

        $retryAfter = max(
            1,
            $limit->getRetryAfter()->getTimestamp() - time(),
        );

        throw new TooManyRequestsHttpException(
            retryAfter: $retryAfter,
            message: 'Too many contact requests. Try again later.',
        );
    }
}
