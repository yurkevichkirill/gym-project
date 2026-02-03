<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Service;

use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\TrainerWorkTime\Service\TrainerWorkTimeServiceInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class TrainerWorkTimeService implements TrainerWorkTimeServiceInterface
{
    public function __construct(
        private TrainerRepository $trainerRepo,
        private TrainerWorkTimeRepository $trainerWorkTimeRepo
    )
    {}

    public function findBy(int $id, array $sort, ?\DateTimeImmutable $date): array
    {
        $trainer = $this->trainerRepo->find($id);
        if(is_null($trainer)) {
            throw new NotFoundHttpException("Trainer not found");
        }

        $criteria = ['trainer' => $trainer];
        if($date) {
            $criteria['date'] = $date;
        }

        return $this->trainerWorkTimeRepo->findBy($criteria, $sort);
    }
}
