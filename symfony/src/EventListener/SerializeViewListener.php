<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Booking\DTO\BookingResponse;
use App\Booking\Entity\Booking;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::VIEW)]
final class SerializeViewListener
{
    public function __invoke(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if (!$result instanceof Booking) {
            return;
        }

        $dto = BookingResponse::fromEntity($result);

        $response = new JsonResponse($dto, 201);
        $event->setResponse($response);
    }
}
