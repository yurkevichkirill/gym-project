<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Service;

use App\Booking\Exception\DateTimeAlreadyTakenException;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\CreateWorkTimeRequest;
use App\TrainerWorkTime\DTO\UpdateWorkTimeRequest;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\User\Service\AvailabilityService;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final readonly class WorkTimeManager
{
    public function __construct(
        private TrainerWorkTimeRepository $worktimeRepo,
        private AvailabilityService $userAvailabilityService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $worktimeLogger,
    )
    {}

    /**
     * @throws DateMalformedStringException|DateTimeAlreadyTakenException|Throwable
     */
    public function create(Trainer $trainer, CreateWorkTimeRequest $requestDto): TrainerWorkTime
    {
        $context = $this->context($trainer, $requestDto);

        $this->worktimeLogger->info(
            'Worktime creation started',
            $this->event($context, 'create', 'started')
        );

        try {
            $this->userAvailabilityService->ensureNotBlocked($trainer);
            $this->userAvailabilityService->ensureNotDeleted($trainer);
        } catch (Throwable $e) {
            $this->worktimeLogger->notice(
                'Trainer is blocked',
                $this->event($context, 'create', 'rejected', [
                    'exception' => $e,
                ])
            );
            throw $e;
        }

        $date = new DateTimeImmutable($requestDto->date);

        $bookingDateTime = new DateTimeImmutable($requestDto->date . ' ' . $requestDto->startTime);
        $startDateTime = new DateTimeImmutable($requestDto->date . ' ' . $requestDto->startTime);
        $endDateTime = new DateTimeImmutable($requestDto->date . ' ' . $requestDto->endTime);
        $now = new DateTimeImmutable();

        if ($endDateTime <= $startDateTime) {
            throw new BadRequestHttpException('End time must be greater than start time');
        }

        if ($bookingDateTime <= $now) {
            $this->worktimeLogger->warning(
                'Attempt to create worktime in the past',
                $this->event($context, 'create', 'rejected', [
                    'booking_datetime' => $bookingDateTime->format('c'),
                    'now' => $now->format('c'),
                ])
            );

            throw new BadRequestHttpException('Cannot create worktime in the past');
        }

        $exists = $this->worktimeRepo->findOneBy([
            'trainer' => $trainer,
            'date' => $date
        ]);

        if ($exists !== null) {
            $this->worktimeLogger->notice(
                'Worktime already exists for this date',
                $this->event($context, 'create', 'rejected')
            );

            throw new DateTimeAlreadyTakenException("Trainer already have worktime in this date");
        }

        $worktime = new TrainerWorkTime();
        $worktime->setTrainer($trainer);
        $worktime->setStartTime($startDateTime);
        $worktime->setEndTime($endDateTime);
        $worktime->setDate($date);

        $this->worktimeRepo->create($worktime);

        $this->entityManager->flush();

        $this->worktimeLogger->info(
            'Worktime created successfully',
            $this->event(
                $this->contextFromEntity($worktime),
                'create',
                'succeeded'
            )
        );

        return $worktime;
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateTimeAlreadyTakenException
     * @throws DateMalformedIntervalStringException|Throwable
     */
    /**
     * @throws DateMalformedStringException
     * @throws DateTimeAlreadyTakenException
     * @throws DateMalformedIntervalStringException|Throwable
     */
    public function update(TrainerWorkTime $worktime, UpdateWorkTimeRequest $dto, $byAdmin = false): TrainerWorkTime
    {
        $context = $this->contextFromEntity($worktime);

        $this->worktimeLogger->info(
            'Worktime update started',
            $this->event($context, 'update', 'started')
        );

        try {
            if (!$byAdmin) {
                $this->userAvailabilityService->ensureNotDeleted($worktime->getTrainer());
                $this->userAvailabilityService->ensureNotBlocked($worktime->getTrainer());
            }

            $newStartTimeStr = $dto->startTime ?? $worktime->getStartTime()->format("H:i:s");
            $newEndTimeStr = $dto->endTime ?? $worktime->getEndTime()->format("H:i:s");

            $dateStr = $worktime->getDate()->format('Y-m-d');
            $newStartDateTime = new DateTimeImmutable($dateStr . ' ' . $newStartTimeStr);
            $newEndDateTime = new DateTimeImmutable($dateStr . ' ' . $newEndTimeStr);

            if ($newEndDateTime <= $newStartDateTime) {
                throw new BadRequestHttpException('End time must be after start time');
            }

            $trainings = $worktime->getTrainings();
            if (count($trainings) > 0) {
                $firstTrainingStart = null;
                $lastTrainingEnd = null;

                foreach ($trainings as $training) {
                    $tStart = $training->getStartTime();

                    $duration = $training->getDurationMinutes();
                    $tEnd = $tStart->modify("+$duration minutes");

                    if ($firstTrainingStart === null || $tStart < $firstTrainingStart) {
                        $firstTrainingStart = $tStart;
                    }
                    if ($lastTrainingEnd === null || $tEnd > $lastTrainingEnd) {
                        $lastTrainingEnd = $tEnd;
                    }
                }

                if ($newStartDateTime > $firstTrainingStart || $newEndDateTime < $lastTrainingEnd) {
                    $this->worktimeLogger->warning(
                        'Worktime update rejected: intersects with existing trainings',
                        $this->event($context, 'update', 'rejected', [
                            'newStart' => $newStartDateTime->format('c'),
                            'newEnd' => $newEndDateTime->format('c'),
                            'firstTraining' => $firstTrainingStart->format('c'),
                            'lastTraining' => $lastTrainingEnd->format('c'),
                        ])
                    );

                    throw new DateTimeAlreadyTakenException("Trainer already has a training in this time interval");
                }
            }

            $worktime->setStartTime($newStartDateTime);
            $worktime->setEndTime($newEndDateTime);

            $this->entityManager->flush();

            $this->worktimeLogger->info(
                'Worktime updated successfully',
                $this->event($this->contextFromEntity($worktime), 'update', 'succeeded')
            );

            return $worktime;
        } catch (Throwable $e) {
            $this->worktimeLogger->error(
                'Worktime update failed',
                $this->event($context, 'update', 'failed', [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                ])
            );

            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function remove(TrainerWorkTime $worktime): void
    {
        $context = $this->contextFromEntity($worktime);

        $this->worktimeLogger->info(
            'Worktime removal started',
            $this->event($context, 'remove', 'started')
        );

        try {
            if (!$worktime->getTrainings()->isEmpty()) {
                $this->worktimeLogger->notice(
                    'Worktime removal rejected: trainings exist',
                    $this->event($context, 'remove', 'rejected')
                );

                throw new ConflictHttpException("Cannot remove work time slot with active trainings.");
            }

            $this->worktimeRepo->remove($worktime);
            $this->entityManager->flush();

            $this->worktimeLogger->info(
                'Worktime removed successfully',
                $this->event($context, 'remove', 'succeeded')
            );
        } catch (Throwable $e) {
            $this->worktimeLogger->error(
                'Worktime removal failed',
                $this->event($context, 'remove', 'failed', [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                ])
            );

            throw $e;
        }
    }

    /**
     * @throws NotFoundHttpException
     */
    public function getAvailable(TrainerWorkTime $worktime): TrainerWorkTime
    {
        $trainer = $worktime->getTrainer();

        if ($trainer->getDeletedAt() !== null || $trainer->getBlockedAt() !== null) {
            throw new NotFoundHttpException('Work time slot not found');
        }

        return $worktime;
    }

    private function context(?Trainer $trainer = null, ?CreateWorkTimeRequest $dto = null): array
    {
        return [
            'domain' => 'worktime',
            'trainer_id' => $trainer?->getId(),
            'date' => $dto?->date,
            'start_time' => $dto?->startTime,
            'end_time' => $dto?->endTime,
        ];
    }

    private function contextFromEntity(TrainerWorkTime $worktime): array
    {
        return [
            'domain' => 'worktime',
            'worktime_id' => $worktime->getId(),
            'trainer_id' => $worktime->getTrainer()?->getId(),
            'date' => $worktime->getDate()?->format('Y-m-d'),
            'start_time' => $worktime->getStartTime()?->format('H:i:s'),
            'end_time' => $worktime->getEndTime()?->format('H:i:s'),
        ];
    }

    private function event(array $context, string $operation, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
                'operation' => $operation,
                'outcome' => $outcome,
            ];
    }
}
