<?php

declare(strict_types=1);

namespace App\Training\Service;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Exception\TrainingWithoutBookingException;
use App\Booking\Service\BookingAvailabilityService;
use App\Client\Entity\Client;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Exception\TrainerWorktimeNotFoundException;
use App\TrainerWorkTime\Exception\WorktimeNotFoundException;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\DTO\TrainingUpdateRequestDTO;
use App\Training\Entity\Training;
use App\User\Exception\UserNotFoundException;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
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

            $clientId = $booking->getClient()->getId();
            $newWorktimeId = $newWorktime->getId();
            $trainingId = $training->getId();

            return $this->entityManager->wrapInTransaction(function () use ($trainingId, $newWorktimeId, $clientId, $newDate, $newStartTime) {
                $lockedClient = $this->entityManager->find(
                    Client::class,
                    $clientId,
                    LockMode::PESSIMISTIC_WRITE
                );
                if ($lockedClient === null) {
                    throw new UserNotFoundException("Client not found");
                }

                $lockedWorktime = $this->entityManager->find(
                    TrainerWorkTime::class,
                    $newWorktimeId,
                    LockMode::PESSIMISTIC_WRITE
                );
                if ($lockedWorktime === null) {
                    throw new TrainerWorktimeNotFoundException();
                }

                $lockedTraining = $this->entityManager->find(
                    Training::class,
                    $trainingId,
                    LockMode::PESSIMISTIC_WRITE
                );
                if ($lockedTraining === null) {
                    throw new TrainingWithoutBookingException();
                }

                $this->entityManager->refresh($lockedWorktime);
                $this->entityManager->refresh($lockedTraining);

                $this->bookingAvailabilityService->checkUpdateBookingAvailability(
                    $lockedTraining,
                    $lockedClient,
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
            if ($booking === null) {
                throw new TrainingWithoutBookingException();
            }

            $bookingId = $booking->getId();
            if ($bookingId === null) {
                throw new TrainingWithoutBookingException('Training booking must be persisted');
            }

            return $this->entityManager->wrapInTransaction(function () use ($bookingId) {
                $lockedBooking = $this->lockBooking($bookingId);
                $lockedTraining = $lockedBooking->getTraining();
                if ($lockedTraining === null) {
                    throw new TrainingWithoutBookingException();
                }

                $this->bookingAvailabilityService->checkCompleteBookingAvailability($lockedTraining);

                $lockedBooking->setStatus(BookingStatusEnum::COMPLETED);

                return $lockedTraining;
            });
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

    private function lockBooking(int $bookingId): Booking
    {
        $this->entityManager->getConnection()->executeStatement(
            'SELECT id FROM booking WHERE id = :id FOR UPDATE',
            ['id' => $bookingId]
        );

        $lockedBooking = $this->entityManager->find(Booking::class, $bookingId);
        if ($lockedBooking === null) {
            throw new TrainingWithoutBookingException('Training booking was not found');
        }

        $this->entityManager->refresh($lockedBooking);

        return $lockedBooking;
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
