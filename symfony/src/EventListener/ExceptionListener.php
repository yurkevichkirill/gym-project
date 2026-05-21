<?php

declare(strict_types=1);

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

final readonly class ExceptionListener
{
    public function __construct(
        private LoggerInterface $requestLogger,
        private KernelInterface $kernel,
    ) {}

    /**
     * @throws SuspiciousOperationException
     */
    #[AsEventListener(event: KernelEvents::EXCEPTION)]
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        $statusCode = 500;

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        } else {
            $reflection = new ReflectionClass($exception);
            $attributes = $reflection->getAttributes(WithHttpStatus::class);

            if (count($attributes) > 0) {
                /** @var WithHttpStatus $attributeInstance */
                $attributeInstance = $attributes[0]->newInstance();
                $statusCode = $attributeInstance->statusCode;
            }
        }

        $context = [
            'domain' => 'request',
            'operation' => 'http_exception',
            'outcome' => $statusCode >= 500 ? 'failed' : 'rejected',
            'status' => $statusCode,
            'exception_class' => $exception::class,
            'message' => $exception->getMessage(),
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
        ];

        if ($statusCode >= 500) {
            $this->requestLogger->error('Unhandled application exception', $context + [
                'exception' => $exception,
            ]);
        } else {
            $this->requestLogger->warning('Domain or HTTP exception handled', $context);
        }

        $isDev = $this->kernel->getEnvironment() === 'dev';
        $errorMessage = ($statusCode >= 500 && !$isDev)
            ? 'Internal Server Error'
            : $exception->getMessage();

        $responseData = [
            'message' => $errorMessage,
        ];

        if ($isDev) {
            $responseData['trace'] = $exception->getTrace();
        }

        $event->setResponse(new JsonResponse($responseData, $statusCode));
    }
}
