<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Trainer;
use App\Enum\DayOfWeekEnum;

class TrainerService implements TrainerServiceInterface
{

    /**
     * @throws \DateMalformedStringException
     */
    public function getAvailable(Trainer $trainer, DayOfWeekEnum $dayOfWeek): array
    {
        $trainerAvailabilities = (array) $trainer->getTrainerAvailabilities();
        $trainings = (array) $trainer->getTrainings();
        $dayAvailabilities = array_filter($trainerAvailabilities, fn($item) => $item->getDayOfWeek() === $dayOfWeek)[0];
        $dayTrainings = array_filter($trainings, fn($item) => $item->getDayOfWeek() === $dayOfWeek);

        $startTrainerTime = $dayAvailabilities->getStartTime();
        $endTrainerTime = $dayAvailabilities->getEndTime();

        $trainings = [];
        foreach ($dayTrainings as $dayTraining) {
            $startTrainingTime = $dayTraining->getStartTime();
            $duration = $dayTraining->getDurationMinutes();
            $endTrainingTime = $startTrainingTime->add(new \DateTimeImmutable('PT' . $duration . 'M'));

            $trainings[$startTrainingTime] = $endTrainingTime;
        }

        ksort($trainings);
        $available = [];
        $startPeriod = $startTrainerTime;
        foreach ($trainings as $startTrainingTime => $endTrainingTime) {
            $available[$startPeriod] = $startTrainingTime;
            $startPeriod = $endTrainingTime;
        }
        $available[$startPeriod] = $endTrainerTime;

        return $available;
    }

    public function getScheduled(Trainer $trainer, DayOfWeekEnum $dayOfWeek): array
    {
        // TODO: Implement getScheduled() method.
    }
}
