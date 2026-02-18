<?php

declare(strict_types=1);

namespace App\Training\Mapper;

use App\Trainer\DTO\TrainerResponse;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Training\DTO\TrainingResponse;
use App\Training\Entity\Training;
use App\TrainingType\TrainingTypeServiceInterface;

class TrainingMapper implements TrainingMapperInterface
{
    public function map(Training $training): TrainingResponse
    {
        return TrainingResponse::fromEntity($training);
    }
}
