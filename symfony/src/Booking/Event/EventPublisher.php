<?php

declare(strict_types=1);

namespace App\Booking\Event;

use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class EventPublisher
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function publish(BookingCreatedEvent $event): void
    {
        $this->bus->dispatch($event);
    }
}
