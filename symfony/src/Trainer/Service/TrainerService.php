<?php

declare(strict_types=1);

namespace App\Trainer\Service;

use App\Availability\Entity\TrainerAvailability;
use App\Enum\DayOfWeekEnum;
use App\Trainer\Entity\Trainer;
use App\Training\Entity\Training;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;

class TrainerService implements TrainerServiceInterface
{

    /**
     * @throws \DateMalformedIntervalStringException
     */
    public function getAvailable(Trainer $trainer, DayOfWeekEnum $dayOfWeek, EntityManagerInterface $em): array
    {
        $dayAvailabilities = $em->getRepository(TrainerAvailability::class)->findBy([
            'trainer' => $trainer,
            'day_of_week' => $dayOfWeek
        ])[0];
        $dayTrainings = $em->getRepository(Training::class)->findBy([
            'trainer' => $trainer,
            'day_of_week' => $dayOfWeek
        ]);

        $startTrainerTime = $dayAvailabilities->getStartTime();
        $endTrainerTime = $dayAvailabilities->getEndTime();
        usort($dayTrainings, fn ($training1, $training2) => $training1->getStartTime() <=> $training2->getStartTime());

        $available = [];
        $startPeriod = $startTrainerTime;
        foreach ($dayTrainings as $dayTraining) {
            $available[] = [
                "start" => $startPeriod,
                "end" => $dayTraining->getStartTime()
            ];
            $startPeriod = $dayTraining->getStartTime()->add(new DateInterval("PT" . $dayTraining->getDurationMinutes() . "M"));
        }
        $available[] = [
            "start" => $startPeriod,
            "end" => $endTrainerTime
        ];

        return $available;
    }

    public function getScheduled(Trainer $trainer, DayOfWeekEnum $dayOfWeek): array
    {
        // TODO: Implement getScheduled() method.
    }
}
