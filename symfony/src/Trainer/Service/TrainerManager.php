<?php

declare(strict_types=1);

namespace App\Trainer\Service;

use App\Trainer\DTO\UpdateTrainerRequest;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;

class TrainerManager
{
    public function __construct(
        private TrainerRepository $trainerRepo,
        private TrainerWorkTimeRepository $worktimeRepo,
    )
    {}

    public function update(Trainer $trainer, UpdateTrainerRequest $requestDto): Trainer
    {
        if($requestDto->phone) {
            $trainer->setPhone($requestDto->phone);
        }
        if($requestDto->price) {
            $trainer->setPrice($requestDto->price);
        }

        $this->trainerRepo->save();

        return $trainer;
    }

    public function softDelete(Trainer $trainer): void
    {
        foreach ($trainer->getTrainerWorkTime() as $worktime) {
            $this->worktimeRepo->remove($worktime);
        }

        $this->trainerRepo->remove($trainer);
    }
}
