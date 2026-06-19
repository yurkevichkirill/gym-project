<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Security\AuthenticationRateLimiter;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AuthenticationRateLimitListener
{
    public function __construct(
        private AuthenticationRateLimiter $rateLimiter,
    ) {}

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isMethod(Request::METHOD_POST)) {
            return;
        }

        switch ($request->getPathInfo()) {
            case '/api/login/':
                $this->rateLimiter->consumeLoginIp($request);
                break;

            case '/api/refresh/':
                $this->rateLimiter->consumeRefreshIp($request);
                break;

            case '/api/clients/activate/':
                $this->rateLimiter->consumeActivationIp($request);
                break;

            case '/api/client/registration/':
                $this->rateLimiter->consumeRegistrationIp($request);
                break;
        }
    }
}
