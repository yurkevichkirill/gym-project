<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Service;

use App\Exception\DateTimeAlreadyTakenException;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\CreateWorkTimeRequest;
use App\TrainerWorkTime\DTO\UpdateWorkTimeRequest;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;

class WorkTimeManager
{
    public function __construct(
        private TrainerWorkTimeRepository $worktimeRepo,
    )
    {}

    /**
     * @throws DateMalformedStringException|DateTimeAlreadyTakenException
     */
    public function create(Trainer $trainer, CreateWorkTimeRequest $requestDto): TrainerWorkTime
    {
        $date = new DateTimeImmutable($requestDto->date);
        $count = count($this->worktimeRepo->findBy([
            'trainer' => $trainer,
            'date' => $date,
        ]));

        if($count > 0) {
            throw new DateTimeAlreadyTakenException("Trainer already have worktime in this date");
        }

        $worktime = new TrainerWorkTime();
        $worktime->setTrainer($trainer);
        $worktime->setStartTime(new DateTimeImmutable($requestDto->startTime));
        $worktime->setEndTime(new DateTimeImmutable($requestDto->endTime));
        $worktime->setDate($date);
        $this->worktimeRepo->create($worktime);

        return $worktime;
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateTimeAlreadyTakenException
     * @throws DateMalformedIntervalStringException
     */
    public function update(TrainerWorkTime $worktime, UpdateWorkTimeRequest $dto): TrainerWorkTime
    {
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
                throw new DateTimeAlreadyTakenException("Trainer already have training in this time");
            }
        }

        $worktime->setStartTime(new DateTimeImmutable($newStartTime));
        $worktime->setEndTime(new DateTimeImmutable($newEndTime));
        $this->worktimeRepo->save();

        return $worktime;
    }

    /**
     * @throws DateMalformedIntervalStringException
     * @throws DateMalformedStringException
     */
    public function isTimeAvailable(TrainerWorkTime $worktime, string $startTime, int $durationMinutes, string $oldStartTime, int $oldDurationMinutes): bool
    {
        $endTime = new DateTimeImmutable($startTime)
            ->add(new DateInterval('PT' . $durationMinutes . 'M'))
            ->format('H:i:s');
        $freeSlots = $worktime->getFreeSlots();
        $freeSlotsExceptCurrent = $this->getFreeSlotsExcept($freeSlots, $oldStartTime, $oldDurationMinutes);

        return array_any($freeSlotsExceptCurrent, fn($slot) => $startTime >= $slot['start'] && $endTime <= $slot['end']);
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    private function getFreeSlotsExcept(array $freeSlots, string $oldStartTime, int $oldDurationMinutes): array
    {
        $excludeSlot = [
            'start' => $oldStartTime,
            'end' => new DateTimeImmutable($oldStartTime)
                ->add(new DateInterval('PT' . $oldDurationMinutes . 'M'))
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
