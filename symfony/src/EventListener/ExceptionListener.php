<?php

namespace App\EventListener;

use App\Exception\DateRescheduledException;
use App\Exception\DateTimeAlreadyTakenException;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionListener
{
    #[AsEventListener(event: KernelEvents::EXCEPTION)]
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = match (true) {
            $exception instanceof NotFoundHttpException => 404,
            $exception instanceof BadRequestHttpException => 400,
            $exception instanceof DateRescheduledException ||
            $exception instanceof LogicException => 422,
            $exception instanceof DateTimeAlreadyTakenException => 409,

            default => 500
        };

        $response = new JsonResponse([
            'error' => $exception->getMessage(),
            'trace' => $exception->getTrace(),
        ], $statusCode);

        $event->setResponse($response);
    }
}
