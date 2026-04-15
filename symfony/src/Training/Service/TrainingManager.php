<?php

declare(strict_types=1);

namespace App\Training\Service;

use App\Booking\Enum\BookingStatusEnum;
use App\Exception\DateRescheduledException;
use App\Exception\NoActiveMembershipException;
use App\Membership\Service\VisitingService;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\DTO\TrainingUpdateRequest;
use App\Training\Entity\Training;
use App\Client\Service\AvailabilityService as ClientAvailabilityService;
use App\TrainerWorkTime\Service\AvailabilityService as WorktimeAvailabilityService;
use App\Training\Repository\TrainingRepository;
use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class TrainingManager
{
    const int MIN_DAY_CHANGE = 1;
    public function __construct(
        private TrainerWorkTimeRepository $worktimeRepo,
        private ClientAvailabilityService $clientAvailabilityService,
        private WorktimeAvailabilityService $worktimeAvailabilityService,
        private VisitingService $visitingService,
        private EntityManagerInterface $entityManager,
    )
    {}

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     * @throws DateRescheduledException
     */
    public function update(Training $training, TrainingUpdateRequest $requestDto): Training
    {
        if ($training->getBooking()?->getStatus() !== BookingStatusEnum::SCHEDULED) {
            throw new ConflictHttpException("Only scheduled trainings can be updated");
        }

        $oldStartTime = $training->getStartTime()->format("H:i:s");

        $newStartTime = $requestDto->startTime
            ?? $training->getStartTime()->format('H:i:s');

        $durationMinutes = $training->getDurationMinutes();

        $newDate = $requestDto->date
            ? new DateTimeImmutable($requestDto->date)
            : $training->getTrainerWorkTime()->getDate();

        $client = $training->getBooking()->getClient();

        if (!$this->visitingService->hasActiveMembership($client, $newDate)) {
            throw new NoActiveMembershipException('Client does not have an active membership for this date');
        }

        $newDateTime = new DateTimeImmutable(
            $newDate->format('Y-m-d') . ' ' . $newStartTime
        );

        if ($newDateTime <= new DateTimeImmutable()) {
            throw new BadRequestHttpException('Cannot book training in the past');
        }

        if ($newDate->format('Y-m-d') === $training->getTrainerWorkTime()->getDate()->format("Y-m-d")) {
            $worktime = $training->getTrainerWorkTime();
        } else {
            if ($newDate < new DateTimeImmutable()->add(new DateInterval('P' . self::MIN_DAY_CHANGE . 'D'))) {
                throw new DateRescheduledException("The minimum reschedule date must be no earlier than tomorrow.");
            }

            $worktime = $this->worktimeRepo->findOneBy(['date' => $newDate]);

            if (!$worktime) {
                throw new NotFoundHttpException("There is no work time for this date");
            }

            $training->setTrainerWorkTime($worktime);
        }

        if (!$this->worktimeAvailabilityService->isTimeAvailable($worktime, $newStartTime, $durationMinutes, $oldStartTime)) {
            throw new DateRescheduledException("Trainer doesn't work at this time");
        }
        if (!$this->clientAvailabilityService->isClientAvailableInDate($client, $newDate, $newStartTime, $durationMinutes,  $oldStartTime)) {
            throw new DateRescheduledException("Client already have training at this time");
        }

        $training->setStartTime(new DateTimeImmutable($newStartTime));

        $this->entityManager->flush();

        return $training;
    }

    public function cancel(Training $training, bool $isAdmin = false): void
    {
        $booking = $training->getBooking();
        if ($isAdmin) {
            $booking->cancel(BookingStatusEnum::CANCELED_BY_SYSTEM);
        } else {
            $booking->cancel(BookingStatusEnum::CANCELED_BY_TRAINER);
        }

        $this->entityManager->flush();
    }

    public function complete(Training $training): Training
    {
        if ($training->getTrainerWorkTime()->getDate() > new DateTimeImmutable()) {
            throw new BadRequestHttpException('Training has not happened yet');
        }

        if ($training->getBooking()->getStatus() !== BookingStatusEnum::SCHEDULED) {
            throw new ConflictHttpException("Only scheduled trainings can be completed");
        }

        $training->getBooking()->setStatus(BookingStatusEnum::COMPLETED);

        $this->entityManager->flush();

        return $training;
    }
}
