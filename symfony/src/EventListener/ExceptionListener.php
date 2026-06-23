<?php

declare(strict_types=1);

namespace App\EventListener;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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
        $isUniqueConstraintViolation = $exception instanceof UniqueConstraintViolationException;

        $statusCode = 500;
        $headers = [];

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $headers = $exception->getHeaders();
        } elseif ($isUniqueConstraintViolation) {
            $statusCode = 409;
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
        if ($isUniqueConstraintViolation) {
            $errorMessage = 'A resource with the same unique value already exists';
        } else {
            $errorMessage = ($statusCode >= 500 && !$isDev)
                ? 'Internal Server Error'
                : $exception->getMessage();
        }

        $event->setResponse(
            new JsonResponse(
                data: [
                    'message' => $errorMessage,
                ],
                status: $statusCode,
                headers: $headers
            )
        );
    }
}
