<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Booking\DTO\BookingResponse;
use App\Booking\Entity\Booking;
use App\Response\OkResponse;
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

        if ($result instanceof OkResponse) {
            if(is_array($result->data)) {
                $event->setResponse(new JsonResponse(
                    [
                        'data' => $result->data,
                        'meta' => [
                            'pagination' => [
                                'page' => $result->page,
                                'limit' => $result->limit,
                                'total' => $result->total,
                                'pages' => (int) ceil($result->total / $result->limit),
                            ],
                            'sort' => $result->sort,
                        ],
                    ],
                    $result->status,
                ));
            } else {
                $event->setResponse(new JsonResponse(
                    [
                        'data' => $result->data
                    ],
                    $result->status,
                ));
            }

        }
    }
}
