<?php

declare(strict_types=1);

namespace App\Training\Service;

use App\Enum\DayOfWeekEnum;
use App\Trainer\Repository\TrainerRepository;
use App\Training\Repository\TrainingRepository;
use App\Training\Service\TrainingServiceInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class TrainingService implements TrainingServiceInterface
{
    public function __construct(
        private TrainerRepository  $trainerRepo,
        private TrainingRepository $trainingRepo
    )
    {}

    public function findBy(
        int $trainerId,
        array $sort,
        ?DayOfWeekEnum $dayOfWeek = null
    ): array
    {
        $trainer = $this->trainerRepo->find($trainerId);
        if(is_null($trainer)) {
            throw new NotFoundHttpException('Trainer not found');
        }
        $criteria = ['trainer' => $trainer];
        if($dayOfWeek) {
            $criteria['dayOfWeek'] = $dayOfWeek;
        }

        return $this->trainingRepo->findBy($criteria, $sort);
    }
}
