<?php

declare(strict_types=1);

namespace App\Training\Service;

use App\Enum\DayOfWeekEnum;
use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\Repository\TrainingRepository;
use App\Training\Service\TrainingServiceInterface;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class TrainingService implements TrainingServiceInterface
{
    public function __construct(
        private TrainerRepository  $trainerRepo,
        private TrainerWorkTimeRepository $trainerWorkTimeRepo,
        private TrainingRepository $trainingRepo
    )
    {}

    public function findBy(
        int $trainerId,
        array $sort,
        ?DateTimeImmutable $date = null
    ): array
    {
        $trainer = $this->trainerRepo->find($trainerId);
        if(is_null($trainer)) {
            throw new NotFoundHttpException('Trainer not found');
        }
        $criteria = ['trainer' => $trainer];
        if($date) {
            $criteria['date'] = $date;
        }

        $trainerWorkTimes = $this->trainerWorkTimeRepo->findBy($criteria);

        $trainings = [];
        foreach ($trainerWorkTimes as $worktime) {
            $dayTrainings = $this->trainingRepo->findBy(['trainerWorkTime' => $worktime], $sort);
            $trainings = array_merge($trainings, $dayTrainings);
        }

        return $trainings;
    }
}
