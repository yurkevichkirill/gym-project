<?php

namespace App\EventListener;

use LogicException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionListener
{
    #[AsEventListener(event: KernelEvents::EXCEPTION)]
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = match (true) {
            $exception instanceof NotFoundHttpException => 404,
            $exception instanceof BadRequestHttpException  => 400,
            $exception instanceof LogicException => 422,
            $exception instanceof ConflictHttpException => 409,
            $exception instanceof UnauthorizedHttpException => 401,
            $exception instanceof AccessDeniedHttpException => 403,

            default => 500
        };

        $responseData = [
            'message' => $exception->getMessage(),
        ];

        if ($_ENV['APP_ENV'] === 'dev') {
            $responseData['trace'] = $exception->getTrace();
        }

        $event->setResponse(new JsonResponse($responseData, $statusCode));
    }
}
