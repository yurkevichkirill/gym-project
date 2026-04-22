<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse\Handler;

use App\Infrastructure\ClickHouse\Buffer\ClickHouseBuffer;
use App\Infrastructure\ClickHouse\Event\AnalyticsEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
final readonly class AnalyticsEventHandler
{
    public function __construct(
        private ClickHouseBuffer $buffer,
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(AnalyticsEvent $event): void
    {
        match (true) {
            str_starts_with($event->eventType, 'booking.') => $this->handleBooking($event),
            str_starts_with($event->eventType, 'membership.') => $this->handleMembership($event),
            default => null,
        };
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function handleMembership(AnalyticsEvent $event): void
    {
        $this->buffer->add('membership_events', [
            'event_id' => $event->eventId,
            'event_time' => $event->occurredAt->format('Y-m-d H:i:s'),
            'event_type' => $event->eventType,

            'client_id' => $event->payload['client_id'],
            'membership_id' => $event->payload['membership_id'],
            'plan_id' => $event->payload['plan_id'],
            'price' => $event->payload['price'],
            'payment_method' => $event->payload['payment_method'],
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function handleBooking(AnalyticsEvent $event): void
    {
        $this->buffer->add('booking_events', [
            'event_id' => $event->eventId,
            'event_time' => $event->occurredAt->format('Y-m-d H:i:s'),
            'event_type' => $event->eventType,

            'client_id' => $event->payload['client_id'],
            'trainer_id' => $event->payload['trainer_id'],
            'booking_id' => $event->payload['booking_id'],

            'price' => $event->payload['price'],
            'payment_method' => $event->payload['payment_method'],
        ]);
    }
}
