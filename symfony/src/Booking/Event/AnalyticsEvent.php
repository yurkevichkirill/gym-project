<?php

declare(strict_types=1);

namespace App\Booking\Event;

use DateTimeImmutable;

interface AnalyticsEvent
{
    public function getEventId(): string;
    public function getOccurredAt(): DateTimeImmutable;
}
