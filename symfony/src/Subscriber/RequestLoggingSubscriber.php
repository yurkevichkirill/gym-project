<?php

declare(strict_types=1);

namespace App\Subscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class RequestLoggingSubscriber implements EventSubscriberInterface
{
    private array $startTimes = [];

    public function __construct(
        private LoggerInterface $requestLogger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onRequest',
            'kernel.response' => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestKey = spl_object_id($request);
        $requestId = $this->resolveOrGenerateRequestId($request);
        $correlationId = $this->resolveCorrelationId($request, $requestId);

        $request->attributes->set('_request_id', $requestId);
        $request->attributes->set('_correlation_id', $correlationId);

        $this->startTimes[$requestKey] = microtime(true);

        $this->requestLogger->info('HTTP request started', [
            'domain' => 'request',
            'operation' => 'http_request',
            'outcome' => 'started',
            'request_id' => $requestId,
            'correlation_id' => $correlationId,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'ip' => $request->getClientIp(),
        ]);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestKey = spl_object_id($request);
        $requestId = $request->attributes->getString('_request_id', '');
        $correlationId = $request->attributes->getString('_correlation_id', $requestId);

        $duration = null;

        if (isset($this->startTimes[$requestKey])) {
            $duration = round((microtime(true) - $this->startTimes[$requestKey]) * 1000);
            unset($this->startTimes[$requestKey]);
        }

        if ($requestId !== '') {
            $response->headers->set('X-Request-Id', $requestId);
        }

        if ($correlationId !== '') {
            $response->headers->set('X-Correlation-Id', $correlationId);
        }

        $this->requestLogger->info('HTTP request finished', [
            'domain' => 'request',
            'operation' => 'http_request',
            'outcome' => 'finished',
            'request_id' => $requestId !== '' ? $requestId : null,
            'correlation_id' => $correlationId !== '' ? $correlationId : null,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
        ]);
    }

    private function resolveOrGenerateRequestId(Request $request): string
    {
        $headerRequestId = trim((string)$request->headers->get('X-Request-Id', ''));
        if ($headerRequestId !== '') {
            return $headerRequestId;
        }

        return bin2hex(random_bytes(16));
    }

    private function resolveCorrelationId(Request $request, string $requestId): string
    {
        $headerCorrelationId = trim((string)$request->headers->get('X-Correlation-Id', ''));
        if ($headerCorrelationId !== '') {
            return $headerCorrelationId;
        }

        return $requestId;
    }
}
