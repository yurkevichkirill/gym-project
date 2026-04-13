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

final readonly class WorkTimeManager
{
    public function __construct(
        private TrainerWorkTimeRepository $worktimeRepo,
        private AvailabilityService $userAvailabilityService,
        private EntityManagerInterface $entityManager,
    )
    {}

    /**
     * @throws DateMalformedStringException|DateTimeAlreadyTakenException
     */
    public function create(Trainer $trainer, CreateWorkTimeRequest $requestDto): TrainerWorkTime
    {
        $this->userAvailabilityService->ensureNotBlocked($trainer);

        $date = new DateTimeImmutable($requestDto->date);
        $count = count($this->worktimeRepo->findBy([
            'trainer' => $trainer,
            'date' => $date,
        ]));

        if($count > 0) {
            throw new DateTimeAlreadyTakenException("OurTrainer already have worktime in this date");
        }

        $worktime = new TrainerWorkTime();
        $worktime->setTrainer($trainer);
        $worktime->setStartTime(new DateTimeImmutable($requestDto->startTime));
        $worktime->setEndTime(new DateTimeImmutable($requestDto->endTime));
        $worktime->setDate($date);

        $this->worktimeRepo->create($worktime);

        $this->entityManager->flush();

        return $worktime;
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateTimeAlreadyTakenException
     * @throws DateMalformedIntervalStringException
     */
    public function update(TrainerWorkTime $worktime, UpdateWorkTimeRequest $dto, $byAdmin = false): TrainerWorkTime
    {
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
                throw new DateTimeAlreadyTakenException("OurTrainer already have training in this time");
            }
        }

        $worktime->setStartTime(new DateTimeImmutable($newStartTime));
        $worktime->setEndTime(new DateTimeImmutable($newEndTime));

        $this->entityManager->flush();

        return $worktime;
    }

    public function remove(TrainerWorkTime $worktime): void
    {
        $trainings = $worktime->getTrainings();

        if (count($trainings) > 0) {
            throw new DateTimeAlreadyTakenException("This date already taken");
        }

        $this->worktimeRepo->remove($worktime);

        $this->entityManager->flush();
    }
}
