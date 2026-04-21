<?php

declare(strict_types=1);

namespace App\Booking\Handler;

use App\Booking\Event\BookingCreatedEvent;
use App\Infrastructure\ClickHouse\Buffer\ClickHouseBuffer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
final readonly class BookingCreatedHandler
{
    public function __construct(
        private ClickHouseBuffer $buffer,
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(BookingCreatedEvent $event): void
    {
        $this->buffer->add('booking_events', [
            'event_id' => $event->eventId,
            'event_time' => $event->occurredAt->format('Y-m-d H:i:s'),
            'event_type' => 'booking_created',

            'client_id' => $event->clientId,
            'trainer_id' => $event->trainerId,
            'booking_id' => $event->bookingId,

            'price' => $event->price,
            'payment_method' => $event->paymentMethod,
            'reason' => '',
        ]);
    }
}
