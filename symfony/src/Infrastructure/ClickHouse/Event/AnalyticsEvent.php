<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse\Event;

use DateTimeImmutable;

final readonly class AnalyticsEvent
{
    public function __construct(
        public string            $eventId,
        public string            $eventType,
        public array             $payload,
        public DateTimeImmutable $occurredAt,
    ) {}
}
