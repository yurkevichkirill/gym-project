<?php

declare(strict_types=1);

namespace App\Booking\Event;

use DateTimeImmutable;

final readonly class BookingCancelledEvent
{
    public function __construct(
        public string             $eventId,
        public int                $bookingId,
        public int                $clientId,
        public string             $reason,
        public DateTimeImmutable $occurredAt,
    ) {}
}
