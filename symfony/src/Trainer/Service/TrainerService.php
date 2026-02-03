<?php

declare(strict_types=1);

namespace App\Trainer\Service;

use App\Enum\DayOfWeekEnum;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\Repository\TrainingRepository;
use App\TrainingType\Repository\TrainingTypeRepository;
use DateInterval;

readonly class TrainerService implements TrainerServiceInterface
{
    public function __construct(
        private TrainerWorkTimeRepository $trainerAvailabilityRepo,
        private TrainingRepository $trainingRepo,
        private TrainerRepository $trainerRepo,
        private TrainingTypeRepository $trainingTypeRepo
    )
    {}

    /**
     * @throws \DateMalformedIntervalStringException
     */
    public function getAvailable(Trainer $trainer, DayOfWeekEnum $dayOfWeek): array
    {
        $dayAvailabilities = $this->trainerAvailabilityRepo->findBy([
            'trainer' => $trainer,
            'day_of_week' => $dayOfWeek
        ])[0];
        $dayTrainings = $this->trainingRepo->findBy([
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

    public function findBy(array $sort, ?int $trainingTypeId): array
    {
        $criteria = [];
        if($trainingTypeId) {
            $trainingType = $this->trainingTypeRepo->find($trainingTypeId);
            $criteria['trainingType'] = $trainingType;
        }
        return $this->trainerRepo->findBy($criteria, $sort);
    }
}
