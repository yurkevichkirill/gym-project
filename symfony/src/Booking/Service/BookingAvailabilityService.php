<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Exception\DateRescheduledException;
use App\Booking\Exception\DateTimeAlreadyTakenException;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Membership\Exception\NoActiveMembershipException;
use App\Membership\Service\MembershipAvailabilityService;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use App\User\Service\AvailabilityService as UserAvailabilityService;
use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class BookingAvailabilityService
{
    public function __construct(
        private UserAvailabilityService $userAvailabilityService,
        private BookingRepository $bookingRepo,
        private MembershipAvailabilityService $membershipAvailabilityService,
    )
    {}

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws DateRescheduledException
     * @throws NoActiveMembershipException
     * @throws DateTimeAlreadyTakenException
     */
    public function checkBookingAvailability(Client $client, ?TrainerWorkTime $worktime, string $date, string $startTime, int $durationMinutes): void
    {
        $this->userAvailabilityService->ensureNotBlocked($client);
        $this->userAvailabilityService->ensureActive($client);

        $bookingDateTime = new DateTimeImmutable($date . ' ' . $startTime);
        if ($bookingDateTime <= new DateTimeImmutable()) {
            throw new BadRequestHttpException('Cannot book training in the past');
        }

        if (!$this->isClientAvailableInDate($client, new DateTimeImmutable($date), $startTime, $durationMinutes)) {
            throw new DateRescheduledException("Client already have training at this time");
        }

        if (!$this->membershipAvailabilityService->hasActiveMembership($client, new DateTimeImmutable($date))) {
            throw new NoActiveMembershipException("Client has no active membership for this date");
        }

        if (!$this->isTimeAvailable($worktime, $startTime, $durationMinutes)) {
            throw new DateTimeAlreadyTakenException();
        }
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     * @throws ConflictHttpException
     * @throws BadRequestHttpException
     */
    public function checkUpdateBookingAvailability(Training $training, Client $client, ?TrainerWorkTime $worktime, DateTimeImmutable $newDate, string $newStartTime): void
    {
        if ($training->getBooking()->getStatus() !== BookingStatusEnum::SCHEDULED) {
            throw new ConflictHttpException("Only scheduled trainings can be updated");
        }

        $oldStartTime = $training->getStartTime()->format("H:i:s");

        $durationMinutes = $training->getDurationMinutes();

        if (!$this->membershipAvailabilityService->hasActiveMembership($client, $newDate)) {
            throw new NoActiveMembershipException('Client does not have an active membership for this date');
        }

        $newDateTime = new DateTimeImmutable(
            $newDate->format('Y-m-d') . ' ' . $newStartTime
        );

        if ($newDateTime <= new DateTimeImmutable()) {
            throw new BadRequestHttpException('Cannot book training in the past');
        }

        $tomorrow = new DateTimeImmutable('tomorrow');

        if ($newDate->setTime(0, 0, 0) < $tomorrow) {
            throw new DateRescheduledException("The minimum reschedule date must be no earlier than tomorrow.");
        }

        if (!$this->isTimeAvailable($worktime, $newStartTime, $durationMinutes, $oldStartTime)) {
            throw new DateRescheduledException("This time is not available for this trainer");
        }

        if (!$this->isClientAvailableInDate($client, $newDate, $newStartTime, $durationMinutes,  $oldStartTime)) {
            throw new DateRescheduledException("Client already have training at this time");
        }
    }

    /**
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     */
    public function checkCompleteBookingAvailability(Training $training): void
    {
        if ($training->getBooking()->getStatus() !== BookingStatusEnum::SCHEDULED) {
            throw new ConflictHttpException("Only scheduled trainings can be completed");
        }

        $fullDate = $training->getTrainerWorkTime()->getDate()->setTime(
            (int) $training->getStartTime()->format('H'),
            (int) $training->getStartTime()->format('i'),
            (int) $training->getStartTime()->format('s')
        );

        $endDateTime = $fullDate->add(new DateInterval("PT{$training->getDurationMinutes()}M"));

        if ($endDateTime > new DateTimeImmutable()) {
            throw new BadRequestHttpException('Training has not happened yet');
        }
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    private function isClientAvailableInDate(
        Client $client,
        DateTimeImmutable $date,
        string $startTime,
        int $durationMinutes,
        ?string $oldStartTime = null
    ): bool
    {
        $clientBusy = $this->getClientBusySlots($client, $date);
        $clientBusyWithoutCurrent = array_filter($clientBusy, fn ($slot) => $slot['start'] !== $oldStartTime);
        $endTime = new DateTimeImmutable($startTime)->add(new DateInterval("PT" . $durationMinutes . "M"))->format('H:i:s');

        return array_all(
            $clientBusyWithoutCurrent,
            fn($busy) => $endTime <= $busy['start'] || $startTime >= $busy['end']
        );
    }

    /**
     * @throws DateMalformedIntervalStringException
     */
    private function getClientBusySlots(Client $client, DateTimeImmutable $date): array
    {
        $bookings = $this->bookingRepo->getActiveClientBookingsByDate($client, $date);

        $clientBusy = [];
        foreach ($bookings as $booking) {
            $clientBusy[] = [
                'start' => $booking->getTraining()->getStartTime()->format('H:i:s'),
                'end' => $booking->getTraining()->getStartTime()->add(new DateInterval("PT" . $booking->getTraining()->getDurationMinutes() . "M"))->format('H:i:s')
            ];
        }

        return $clientBusy;
    }

    /**
     * @throws DateMalformedIntervalStringException
     * @throws DateMalformedStringException
     */
    private function isTimeAvailable(TrainerWorkTime $worktime, string $startTime, int $durationMinutes, ?string $oldStartTime = null): bool
    {
        $endTime = new DateTimeImmutable($startTime)
            ->add(new DateInterval('PT' . $durationMinutes . 'M'))
            ->format('H:i:s');

        $freeSlots = $worktime->getFreeSlots();

        if ($oldStartTime !== null) {
            $freeSlots = $this->getFreeSlotsExcept($freeSlots, $oldStartTime, $durationMinutes);
        }

        return array_any($freeSlots, fn($slot) => $startTime >= $slot['start'] && $endTime <= $slot['end']);
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    private function getFreeSlotsExcept(array $freeSlots, string $oldStartTime, int $durationMinutes): array
    {
        $excludeSlot = [
            'start' => $oldStartTime,
            'end' => new DateTimeImmutable($oldStartTime)
                ->add(new DateInterval('PT' . $durationMinutes . 'M'))
                ->format('H:i:s')
        ];

        $allSlots = array_merge($freeSlots, [$excludeSlot]);
        usort($allSlots, fn($s1, $s2) => $s1['start'] <=> $s2['start']);
        return $this->mergeOverlappingSlots($allSlots);
    }

    private function mergeOverlappingSlots(array $slots): array
    {
        if (empty($slots)) return [];

        $merged = [$slots[0]];

        foreach ($slots as $slot) {
            $last = &$merged[count($merged) - 1];

            if ($slot['start'] <= $last['end']) {
                $last['end'] = max($last['end'], $slot['end']);
            } else {
                $merged[] = $slot;
            }
        }

        return $merged;
    }
}
