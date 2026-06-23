<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

final class RequestCorrelationListener implements EventSubscriberInterface
{
    private const string REQUEST_ID_ATTRIBUTE = '_request_id';
    private const string CORRELATION_ID_ATTRIBUTE = '_correlation_id';
    private const string REQUEST_ID_HEADER = 'X-Request-Id';
    private const string CORRELATION_ID_HEADER = 'X-Correlation-Id';
    private const string VALID_ID_PATTERN = '/^[A-Za-z0-9._:-]{1,128}$/D';

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1024],
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = $this->validHeaderValue(
            $request->headers->get(self::REQUEST_ID_HEADER),
        ) ?? Uuid::v7()->toRfc4122();
        $correlationId = $this->validHeaderValue(
            $request->headers->get(self::CORRELATION_ID_HEADER),
        ) ?? $requestId;

        $request->attributes->set(self::REQUEST_ID_ATTRIBUTE, $requestId);
        $request->attributes->set(self::CORRELATION_ID_ATTRIBUTE, $correlationId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = $request->attributes->getString(self::REQUEST_ID_ATTRIBUTE, '');
        $correlationId = $request->attributes->getString(
            self::CORRELATION_ID_ATTRIBUTE,
            $requestId,
        );

        if ($requestId !== '') {
            $event->getResponse()->headers->set(self::REQUEST_ID_HEADER, $requestId);
        }

        if ($correlationId !== '') {
            $event->getResponse()->headers->set(self::CORRELATION_ID_HEADER, $correlationId);
        }
    }

    private function validHeaderValue(?string $value): ?string
    {
        if ($value === null || preg_match(self::VALID_ID_PATTERN, $value) !== 1) {
            return null;
        }

        return $value;
    }
}
