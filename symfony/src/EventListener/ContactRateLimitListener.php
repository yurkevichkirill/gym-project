<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Contact\Security\ContactRateLimiter;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ContactRateLimitListener
{
    public function __construct(
        private ContactRateLimiter $rateLimiter,
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

        if ($request->getPathInfo() !== '/api/contact/') {
            return;
        }

        $this->rateLimiter->consume($request);
    }
}
