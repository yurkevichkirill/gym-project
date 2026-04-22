<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse\Subscriber;

use App\Infrastructure\ClickHouse\Buffer\ClickHouseBuffer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final readonly class ClickHouseFlushSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ClickHouseBuffer $buffer,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerStoppedEvent::class => 'onStop',
        ];
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function onStop(): void
    {
        $this->buffer->flushAll();
    }
}
