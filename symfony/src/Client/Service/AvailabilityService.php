<?php

declare(strict_types=1);

namespace App\Client\Service;

use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class AvailabilityService
{
    public function __construct(
        private BookingRepository $bookingRepo,
    )
    {}

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    public function isClientAvailableInDate(Client $client, DateTimeImmutable $date, string $startTime, int $durationMinutes, string $oldStartTime): bool
    {
        $clientBusy = $this->getClientBusy($client, $date);
        $clientBusyWithoutCurrent = array_filter($clientBusy, fn ($slot) => $slot['start'] !== $oldStartTime);
        $endTime = new DateTimeImmutable($startTime)->add(new DateInterval("PT" . $durationMinutes . "M"))->format('H:i:s');

        return array_all($clientBusyWithoutCurrent, fn($trainingSlot) => (
                $startTime < $trainingSlot['start'] || $startTime >= $trainingSlot['end']) &&
            ($endTime <= $trainingSlot['start'] || $startTime > $trainingSlot['end'])
        );
    }

    /**
     * @throws DateMalformedIntervalStringException
     */
    public function getClientBusy(Client $client, DateTimeImmutable $date): array
    {
        $bookings = $this->bookingRepo->getClientBookingsByDate($client, $date);

        $clientBusy = [];
        foreach ($bookings as $booking) {
            $clientBusy[] = [
                'start' => $booking->getTraining()->getStartTime()->format('H:i:s'),
                'end' => $booking->getTraining()->getStartTime()->add(new DateInterval("PT" . $booking->getTraining()->getDurationMinutes() . "M"))->format('H:i:s')
            ];
        }

        return $clientBusy;
    }

    public function hasClientEnoughMoney(float $balance, float $price): bool
    {
        return $balance >= $price;
    }
}
