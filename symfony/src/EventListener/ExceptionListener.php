<?php

declare(strict_types=1);

namespace App\EventListener;

use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

final readonly class ExceptionListener
{
    public function __construct(
        private LoggerInterface $requestLogger,
        private KernelInterface $kernel,
    ) {}

    #[AsEventListener(event: KernelEvents::EXCEPTION)]
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        $requestId = $request->attributes->get('_request_id');
        $correlationId = $request->attributes->get('_correlation_id', $requestId);

        $context = [
            'domain' => 'request',
            'operation' => 'http_exception',
            'outcome' => $statusCode >= 500 ? 'failed' : 'rejected',
            'status' => $statusCode,
            'exception_class' => $exception::class,
            'message' => $exception->getMessage(),
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'request_id' => is_string($requestId) && $requestId !== '' ? $requestId : null,
            'correlation_id' => is_string($correlationId) && $correlationId !== '' ? $correlationId : null,
        ];

        if ($statusCode >= 500) {
            $this->requestLogger->error('Unhandled application exception', $context + [
                'exception' => $exception,
            ]);
        } else {
            $this->requestLogger->warning('HTTP exception handled', $context);
        }

        $responseData = [
            'message' => $exception->getMessage(),
            'request_id' => is_string($requestId) && $requestId !== '' ? $requestId : null,
        ];

        if ($this->kernel->getEnvironment() === 'dev') {
            $responseData['trace'] = $exception->getTrace();
        }

        $event->setResponse(new JsonResponse($responseData, $statusCode));
    }
}
