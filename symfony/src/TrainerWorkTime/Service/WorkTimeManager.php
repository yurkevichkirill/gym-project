<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Service;

use App\Booking\Exception\DateTimeAlreadyTakenException;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\CreateWorkTimeRequestDTO;
use App\TrainerWorkTime\DTO\UpdateWorkTimeRequestDTO;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Exception\EndTimeBeforeStartException;
use App\TrainerWorkTime\Exception\PastWorktimeDateException;
use App\TrainerWorkTime\Exception\WorktimeHasActiveTrainingsException;
use App\TrainerWorkTime\Exception\WorktimeNotFoundException;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\Repository\TrainingRepository;
use App\User\Service\AvailabilityService;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class WorkTimeManager
{
    public function __construct(
        private TrainerWorkTimeRepository $worktimeRepo,
        private TrainingRepository $trainingRepo,
        private AvailabilityService $userAvailabilityService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $worktimeLogger,
    )
    {}

    /**
     * @throws DateMalformedStringException|DateTimeAlreadyTakenException|Throwable
     */
    public function create(Trainer $trainer, CreateWorkTimeRequestDTO $requestDto): TrainerWorkTime
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
                    'exception' => $e::class,
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
            throw new EndTimeBeforeStartException();
        }

        if ($bookingDateTime <= $now) {
            $this->worktimeLogger->warning(
                'Attempt to create worktime in the past',
                $this->event($context, 'create', 'rejected', [
                    'booking_datetime' => $bookingDateTime->format('c'),
                    'now' => $now->format('c'),
                ])
            );

            throw new PastWorktimeDateException('Cannot create worktime in the past');
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

            throw new DateTimeAlreadyTakenException('Trainer already have worktime in this date');
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
     * @throws Throwable
     */
    public function update(
        TrainerWorkTime $worktime,
        UpdateWorkTimeRequestDTO $dto,
        bool $byAdmin = false
    ): TrainerWorkTime {
        $context = $this->contextFromEntity($worktime);
        $worktimeId = $worktime->getId();

        if ($worktimeId === null) {
            throw new WorktimeNotFoundException();
        }

        try {
            $updatedWorktime = $this->entityManager->wrapInTransaction(
                function () use ($worktimeId, $dto, $byAdmin): TrainerWorkTime {
                    $lockedWorktime = $this->worktimeRepo->findForUpdate($worktimeId);

                    if ($lockedWorktime === null) {
                        throw new WorktimeNotFoundException();
                    }

                    if (!$byAdmin) {
                        $trainer = $lockedWorktime->getTrainer();

                        $this->userAvailabilityService->ensureNotDeleted($trainer);
                        $this->userAvailabilityService->ensureNotBlocked($trainer);
                    }

                    $date = $lockedWorktime->getDate()->format('Y-m-d');

                    $newStartTime = new DateTimeImmutable(sprintf(
                        '%s %s',
                        $date,
                        $dto->startTime
                        ?? $lockedWorktime->getStartTime()->format('H:i:s'),
                    ));

                    $newEndTime = new DateTimeImmutable(sprintf(
                        '%s %s',
                        $date,
                        $dto->endTime
                        ?? $lockedWorktime->getEndTime()->format('H:i:s'),
                    ));

                    if ($newEndTime <= $newStartTime) {
                        throw new EndTimeBeforeStartException();
                    }

                    $busyTrainings = $this->trainingRepo
                        ->findBusyByWorktime($lockedWorktime);

                    foreach ($busyTrainings as $training) {
                        $trainingStart = new DateTimeImmutable(sprintf(
                            '%s %s',
                            $date,
                            $training->getStartTime()->format('H:i:s'),
                        ));

                        $trainingEnd = $trainingStart->modify(sprintf(
                            '+%d minutes',
                            $training->getDurationMinutes(),
                        ));

                        if (
                            $trainingStart < $newStartTime
                            || $trainingEnd > $newEndTime
                        ) {
                            throw new DateTimeAlreadyTakenException(
                                'Active training is outside the new worktime interval'
                            );
                        }
                    }

                    $lockedWorktime->setStartTime($newStartTime);
                    $lockedWorktime->setEndTime($newEndTime);

                    return $lockedWorktime;
                }
            );

            $this->worktimeLogger->info(
                'Worktime updated successfully',
                $this->event(
                    $this->contextFromEntity($updatedWorktime),
                    'update',
                    'succeeded',
                ),
            );

            return $updatedWorktime;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function remove(TrainerWorkTime $worktime): void
    {
        $context = $this->contextFromEntity($worktime);
        $worktimeId = $worktime->getId();

        if ($worktimeId === null) {
            throw new WorktimeNotFoundException();
        }

        try {
            $this->entityManager->wrapInTransaction(
                function () use ($worktimeId): void {
                    $lockedWorktime = $this->worktimeRepo
                        ->findForUpdate($worktimeId);

                    if ($lockedWorktime === null) {
                        throw new WorktimeNotFoundException();
                    }

                    if (!$lockedWorktime->getTrainings()->isEmpty()) {
                        throw new WorktimeHasActiveTrainingsException(
                            'Cannot remove worktime with associated training history'
                        );
                    }

                    $this->worktimeRepo->remove($lockedWorktime);
                }
            );

            $this->worktimeLogger->info(
                'Worktime removed successfully',
                $this->event($context, 'remove', 'succeeded'),
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function getAvailable(TrainerWorkTime $worktime): TrainerWorkTime
    {
        $trainer = $worktime->getTrainer();

        if ($trainer->getDeletedAt() !== null || $trainer->getBlockedAt() !== null) {
            throw new WorktimeNotFoundException();
        }

        return $worktime;
    }

    /**
     * @return array<string, scalar|null>
     */
    private function context(?Trainer $trainer = null, ?CreateWorkTimeRequestDTO $dto = null): array
    {
        return [
            'domain' => 'worktime',
            'trainer_id' => $trainer?->getId(),
            'date' => $dto?->date,
            'start_time' => $dto?->startTime,
            'end_time' => $dto?->endTime,
        ];
    }

    /**
     * @return array<string, scalar|null>
     */
    private function contextFromEntity(TrainerWorkTime $worktime): array
    {
        return [
            'domain' => 'worktime',
            'worktime_id' => $worktime->getId(),
            'trainer_id' => $worktime->getTrainer()->getId(),
            'date' => $worktime->getDate()->format('Y-m-d'),
            'start_time' => $worktime->getStartTime()->format('H:i:s'),
            'end_time' => $worktime->getEndTime()->format('H:i:s'),
        ];
    }

    /**
     * @param array<string, scalar|null> $context
     * @param array<string, scalar|null> $extra
     * @return array<string, scalar|null>
     */
    private function event(array $context, string $operation, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
                'operation' => $operation,
                'outcome' => $outcome,
            ];
    }
}
