<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Exception\ClientAlreadyBookedException;
use App\Booking\Exception\DateTimeAlreadyTakenException;
use App\Booking\Exception\InvalidBookingStatusException;
use App\Booking\Exception\InvalidRescheduleDateException;
use App\Booking\Exception\PastBookingDateException;
use App\Booking\Exception\TrainingWithoutBookingException;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Membership\Exception\NoActiveMembershipException;
use App\Membership\Service\MembershipAvailabilityService;
use App\Trainer\Exception\TrainerTimeUnavailableException;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Exception\TrainerWorktimeNotFoundException;
use App\TrainerWorkTime\Exception\WorktimeNotFoundException;
use App\Training\Entity\Training;
use App\Training\Exception\TrainingNotFinishedException;
use App\User\Service\AvailabilityService as UserAvailabilityService;
use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class BookingAvailabilityService
{
    /**
     * @phpstan-type TimeSlot array{start: string, end: string}
     */
    public function __construct(
        private UserAvailabilityService $userAvailabilityService,
        private BookingRepository $bookingRepo,
        private MembershipAvailabilityService $membershipAvailabilityService,
    )
    {}


    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    public function checkBookingAvailability(Client $client, ?TrainerWorkTime $worktime, string $date, string $startTime, int $durationMinutes): void
    {
        $this->userAvailabilityService->ensureNotBlocked($client);
        $this->userAvailabilityService->ensureActive($client);

        if ($worktime === null)
        {
            throw new WorktimeNotFoundException();
        }

        $trainer = $worktime->getTrainer();

        $this->userAvailabilityService->ensureNotDeleted($trainer);

        $bookingDateTime = new DateTimeImmutable($date . ' ' . $startTime);
        if ($bookingDateTime <= new DateTimeImmutable()) {
            throw new PastBookingDateException();
        }

        if (!$this->isClientAvailableInDate($client, new DateTimeImmutable($date), $startTime, $durationMinutes)) {
            throw new ClientAlreadyBookedException();
        }

        if (!$this->membershipAvailabilityService->hasActiveMembership($client, new DateTimeImmutable($date))) {
            throw new NoActiveMembershipException('Client has no active membership for this date');
        }

        if (!$this->isTimeAvailable($worktime, $startTime, $durationMinutes)) {
            throw new DateTimeAlreadyTakenException();
        }

        $this->userAvailabilityService->ensureNotDeleted($client);
        $this->userAvailabilityService->ensureNotBlocked($client);
        $this->userAvailabilityService->ensureActive($client);
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    public function checkUpdateBookingAvailability(Training $training, Client $client, ?TrainerWorkTime $worktime, DateTimeImmutable $newDate, string $newStartTime): void
    {
        $booking = $training->getBooking();
        if ($booking === null) {
            throw new TrainingWithoutBookingException();
        }

        if ($worktime === null) {
            throw new TrainerWorktimeNotFoundException();
        }

        if ($booking->getStatus() !== BookingStatusEnum::SCHEDULED) {
            throw new InvalidBookingStatusException('Only scheduled trainings can be updated');
        }

        $oldStartTime = $training->getStartTime()->format('H:i:s');
        $oldWorktime = $training->getTrainerWorkTime();

        $durationMinutes = $training->getDurationMinutes();

        if (!$this->membershipAvailabilityService->hasActiveMembership($client, $newDate)) {
            throw new NoActiveMembershipException('Client does not have an active membership for this date');
        }

        $newDateTime = new DateTimeImmutable(
            $newDate->format('Y-m-d') . ' ' . $newStartTime
        );

        if ($newDateTime <= new DateTimeImmutable()) {
            throw new PastBookingDateException();
        }

        $tomorrow = new DateTimeImmutable('tomorrow');

        if ($newDate->setTime(0, 0, 0) < $tomorrow) {
            throw new InvalidRescheduleDateException('The minimum reschedule date must be no earlier than tomorrow.');
        }

        $oldStartTimeForWorktime = $this->isSameWorktime($oldWorktime, $worktime) ? $oldStartTime : null;

        if (!$this->isTimeAvailable($worktime, $newStartTime, $durationMinutes, $oldStartTimeForWorktime)) {
            throw new TrainerTimeUnavailableException();
        }

        if (!$this->isClientAvailableInDate($client, $newDate, $newStartTime, $durationMinutes, $booking)) {
            throw new ClientAlreadyBookedException();
        }
    }

    public function checkCompleteBookingAvailability(Training $training): void
    {
        $booking = $training->getBooking();
        if ($booking === null) {
            throw new TrainingWithoutBookingException();
        }

        if ($booking->getStatus() !== BookingStatusEnum::SCHEDULED) {
            throw new InvalidBookingStatusException('Only scheduled trainings can be updated');
        }

        $fullDate = $training->getTrainerWorkTime()->getDate()->setTime(
            (int) $training->getStartTime()->format('H'),
            (int) $training->getStartTime()->format('i'),
            (int) $training->getStartTime()->format('s')
        );

        $endDateTime = $fullDate->add(new DateInterval("PT{$training->getDurationMinutes()}M"));

        if ($endDateTime > new DateTimeImmutable()) {
            throw new TrainingNotFinishedException();
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
        ?Booking $bookingToIgnore = null
    ): bool
    {
        $clientBusy = $this->getClientBusySlots($client, $date, $bookingToIgnore);
        $endTime = new DateTimeImmutable($startTime)->add(new DateInterval('PT' . $durationMinutes . 'M'))->format('H:i:s');

        return array_all(
            $clientBusy,
            fn($busy) => $endTime <= $busy['start'] || $startTime >= $busy['end']
        );
    }

    /**
     * @return list<array{start: string, end: string}>
     * @throws DateMalformedIntervalStringException
     * @throws DateMalformedIntervalStringException
     */
    private function getClientBusySlots(Client $client, DateTimeImmutable $date, ?Booking $bookingToIgnore = null): array
    {
        $bookings = $this->bookingRepo->getActiveClientBookingsByDate($client, $date);

        $clientBusy = [];
        foreach ($bookings as $booking) {
            if ($this->isSameBooking($booking, $bookingToIgnore)) {
                continue;
            }

            $training = $booking->getTraining();
            if ($training === null) {
                continue;
            }

            $clientBusy[] = [
                'start' => $training->getStartTime()->format('H:i:s'),
                'end' => $training->getStartTime()->add(new DateInterval('PT' . $training->getDurationMinutes() . 'M'))->format('H:i:s'),
            ];
        }

        return $clientBusy;
    }

    private function isSameWorktime(TrainerWorkTime $currentWorktime, TrainerWorkTime $checkedWorktime): bool
    {
        $currentId = $currentWorktime->getId();
        $checkedId = $checkedWorktime->getId();

        if ($currentId !== null && $checkedId !== null) {
            return $currentId === $checkedId;
        }

        return $currentWorktime === $checkedWorktime;
    }

    private function isSameBooking(Booking $booking, ?Booking $bookingToIgnore): bool
    {
        if ($bookingToIgnore === null) {
            return false;
        }

        $bookingId = $booking->getId();
        $ignoredId = $bookingToIgnore->getId();

        if ($bookingId !== null && $ignoredId !== null) {
            return $bookingId === $ignoredId;
        }

        return $booking === $bookingToIgnore;
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
     * @param list<array{start: string, end: string}> $freeSlots
     * @return list<array{start: string, end: string}>
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
        usort($allSlots, static fn (array $s1, array $s2): int => $s1['start'] <=> $s2['start']);

        return $this->mergeOverlappingSlots($allSlots);
    }

    /**
     * @param list<array{start: string, end: string}> $slots
     * @return list<array{start: string, end: string}>
     */
    private function mergeOverlappingSlots(array $slots): array
    {
        if ($slots === []) {
            return [];
        }

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
