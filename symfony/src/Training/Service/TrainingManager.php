<?php

declare(strict_types=1);

namespace App\Training\Service;

use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Exception\TrainingWithoutBookingException;
use App\Booking\Service\BookingAvailabilityService;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Exception\WorktimeNotFoundException;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\DTO\TrainingUpdateRequestDTO;
use App\Training\Entity\Training;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class TrainingManager
{
    public function __construct(
        private TrainerWorkTimeRepository $worktimeRepo,
        private BookingAvailabilityService $bookingAvailabilityService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $bookingLogger,
    )
    {}

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws DateMalformedIntervalStringException
     */
    public function update(Training $training, TrainingUpdateRequestDTO $requestDto): Training
    {
        $booking = $training->getBooking();

        $loggingContext = [
            'client_id' => $booking?->getClient()->getId() ?? '',
            'trainer_id' => $training->getTrainerWorkTime()->getTrainer()->getId() ?? '',
            'date' => $training->getTrainerWorkTime()->getDate()->format('Y-m-d'),
            'start_time' => $training->getStartTime()->format('H:i:s'),
            'duration_minutes' => $training->getDurationMinutes(),
        ];

        try {
            if ($booking === null) {
                throw new TrainingWithoutBookingException();
            }

            $client = $booking->getClient();
            $trainer = $training->getTrainerWorkTime()->getTrainer();

            $newDate = $requestDto->date !== null
                ? new DateTimeImmutable($requestDto->date)
                : $training->getTrainerWorkTime()->getDate();

            $newStartTime = $requestDto->startTime
                ?? $training->getStartTime()->format('H:i:s');

            $newWorktime = $this->worktimeRepo->findByDateForTrainer(
                $trainer,
                $newDate
            );

            if ($newWorktime === null) {
                throw new WorktimeNotFoundException('There is no work time for this date');
            }

            $newWorktimeId = $newWorktime->getId();
            $trainingId = $training->getId();

            return $this->entityManager->wrapInTransaction(function () use ($trainingId, $newWorktimeId, $client, $newDate, $newStartTime) {
                $this->entityManager->getConnection()->executeStatement(
                    'SELECT id FROM trainer_work_time WHERE id = :id FOR UPDATE',
                    ['id' => $newWorktimeId]
                );
                $lockedWorktime = $this->entityManager->find(TrainerWorkTime::class, $newWorktimeId);
                $this->entityManager->refresh($lockedWorktime);


                $this->entityManager->getConnection()->executeStatement(
                    'SELECT id FROM training WHERE id = :id FOR UPDATE',
                    ['id' => $trainingId]
                );
                $lockedTraining = $this->entityManager->find(Training::class, $trainingId);
                $this->entityManager->refresh($lockedTraining);

                if ($lockedWorktime === null || $lockedTraining === null) {
                    throw new WorktimeNotFoundException('Worktime or training not found during update');
                }

                $this->bookingAvailabilityService->checkUpdateBookingAvailability(
                    $lockedTraining,
                    $client,
                    $lockedWorktime,
                    $newDate,
                    $newStartTime
                );

                $lockedTraining->setTrainerWorkTime($lockedWorktime);

                $lockedTraining->setStartTime(new DateTimeImmutable($newStartTime));

                return $lockedTraining;
            });
        } catch (DomainException $e) {
            $this->bookingLogger->notice('update.rejected', $this->bookingEventContext($loggingContext, 'update', 'rejected', [
                'reason' => $e::class,
            ]));

            throw $e;
        } catch (Throwable $e) {
            $this->bookingLogger->error('updating.failed', $this->bookingEventContext($loggingContext, 'update', 'failed', [
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
            ]));

            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function complete(Training $training): Training
    {
        $booking = $training->getBooking();
        $loggingContext = [
            'client_id' => $booking?->getClient()->getId() ?? '',
            'trainer_id' => $training->getTrainerWorkTime()->getTrainer()->getId() ?? '',
            'date' => $training->getTrainerWorkTime()->getDate()->format('Y-m-d'),
            'start_time' => $training->getStartTime()->format('H:i:s'),
            'duration_minutes' => $training->getDurationMinutes(),
        ];

        try {
            $this->bookingAvailabilityService->checkCompleteBookingAvailability($training);

            if ($booking === null) {
                throw new TrainingWithoutBookingException();
            }

            $booking->setStatus(BookingStatusEnum::COMPLETED);

            $this->entityManager->flush();

            return $training;
        } catch (DomainException $e) {
            $this->bookingLogger->notice('complete.rejected', $this->bookingEventContext($loggingContext, 'complete', 'rejected', [
                'reason' => $e::class,
            ]));

            throw $e;
        } catch (Throwable $e) {
            $this->bookingLogger->error('complete.failed', $this->bookingEventContext($loggingContext, 'complete', 'failed', [
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
            ]));

            throw $e;
        }
    }

    /**
     * @param array<string, scalar|null> $context
     * @param array<string, scalar|null> $extra
     * @return array<string, scalar|null>
     */
    private function bookingEventContext(array $context, string $operation, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
                'domain' => 'booking',
                'operation' => $operation,
                'outcome' => $outcome,
            ];
    }
}
