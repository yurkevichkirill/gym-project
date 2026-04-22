<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse\Publisher;

use App\Infrastructure\ClickHouse\Event\AnalyticsEvent;
use DateTimeImmutable;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class AnalyticsPublisher
{
    public function __construct(
        private MessageBusInterface $bus
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function publish(string $type, array $payload): void
    {
        $this->bus->dispatch(
            new AnalyticsEvent(
                eventId: uuid_create(UUID_TYPE_RANDOM),
                eventType: $type,
                payload: $payload,
                occurredAt: new DateTimeImmutable(),
            )
        );
    }
}
