<?php

declare(strict_types=1);

namespace App\Trainer\Service;

use App\Trainer\DTO\UpdateTrainerRequest;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;

class TrainerManager
{
    public function __construct(
        private TrainerRepository $trainerRepo,
    )
    {}

    public function update(Trainer $trainer, UpdateTrainerRequest $requestDto): Trainer
    {
        if($requestDto->email) {
            $trainer->setEmail($requestDto->email);
        }
        if($requestDto->phone) {
            $trainer->setPhone($requestDto->phone);
        }
        if($requestDto->price) {
            $trainer->setPrice($requestDto->price);
        }

        $this->trainerRepo->save();

        return $trainer;
    }
}
