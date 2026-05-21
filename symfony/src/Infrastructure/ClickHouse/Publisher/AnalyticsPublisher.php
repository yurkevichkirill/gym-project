<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse\Publisher;

use App\Infrastructure\ClickHouse\Event\AnalyticsEvent;
use DateTimeImmutable;
use Random\RandomException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final readonly class AnalyticsPublisher
{
    public function __construct(
        private MessageBusInterface $bus
    ) {}

    /**
     * @param array<string, scalar|null> $payload
     * @throws ExceptionInterface|RandomException
     */
    public function publish(string $type, array $payload): void
    {
        $eventId = Uuid::v4()->toRfc4122();

        $this->bus->dispatch(
            new AnalyticsEvent(
                eventId: $eventId,
                eventType: $type,
                payload: $payload,
                occurredAt: new DateTimeImmutable(),
            )
        );
    }
}
