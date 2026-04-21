<?php

declare(strict_types=1);

namespace App\Booking\Event;

use DateTimeImmutable;

final readonly class BookingCreatedEvent
{
    public function __construct(
        public string            $eventId,
        public int               $clientId,
        public int               $trainerId,
        public int               $bookingId,
        public float             $price,
        public string            $paymentMethod,
        public DateTimeImmutable $occurredAt,
    ) {}
}
