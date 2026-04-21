<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Service;

use App\Exception\DateTimeAlreadyTakenException;
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
        $now = new DateTimeImmutable();

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

        $count = count($this->worktimeRepo->findBy([
            'trainer' => $trainer,
            'date' => $date,
        ]));

        if($count > 0) {
            $this->worktimeLogger->notice(
                'Worktime already exists for this date',
                $this->event($context, 'create', 'rejected')
            );

            throw new DateTimeAlreadyTakenException("Trainer already have worktime in this date");
        }

        $worktime = new TrainerWorkTime();
        $worktime->setTrainer($trainer);
        $worktime->setStartTime(new DateTimeImmutable($requestDto->startTime));
        $worktime->setEndTime(new DateTimeImmutable($requestDto->endTime));
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
    public function update(TrainerWorkTime $worktime, UpdateWorkTimeRequest $dto, $byAdmin = false): TrainerWorkTime
    {
        $context = $this->contextFromEntity($worktime);

        $this->worktimeLogger->info(
            'Worktime update started',
            $this->event($context, 'update', 'started')
        );

        try {
            if (!$byAdmin) {
                $this->userAvailabilityService->ensureNotBlocked($worktime->getTrainer());
            }

            $newStartTime = $dto->startTime ?? $worktime->getStartTime()->format("H:i:s");
            $newEndTime = $dto->endTime ?? $worktime->getEndTime()->format("H:i:s");
            $freeSlots = $worktime->getFreeSlots();
            if (isset($freeSlots[0])) {
                if($worktime->getStartTime()->format("H:i:s") === $freeSlots[0]['start']) {
                    $firstTrainingStartTime = $freeSlots[0]['end'];
                } else {
                    $firstTrainingStartTime = $freeSlots[0]['start'];
                }
                if($worktime->getEndTime()->format("H:i:s") === end($freeSlots)['end']) {
                    $lastTrainingEndTime = end($freeSlots)['start'];
                } else {
                    $lastTrainingEndTime = end($freeSlots)['end'];
                }
                if ($newStartTime > $firstTrainingStartTime || $newEndTime < $lastTrainingEndTime) {
                    $this->worktimeLogger->warning(
                        'Worktime update rejected: intersects with training',
                        $this->event($context, 'update', 'rejected')
                    );

                    throw new DateTimeAlreadyTakenException("OurTrainer already have training in this time");
                }
            }

            $worktime->setStartTime(new DateTimeImmutable($newStartTime));
            $worktime->setEndTime(new DateTimeImmutable($newEndTime));

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
            $trainings = $worktime->getTrainings();

            if (count($trainings) > 0) {
                $this->worktimeLogger->notice(
                    'Worktime removal rejected: trainings exist',
                    $this->event($context, 'remove', 'rejected')
                );

                throw new DateTimeAlreadyTakenException("This date already taken");
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
