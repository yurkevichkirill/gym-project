<?php

declare(strict_types=1);

namespace App\Training\Mapper;

use App\Training\DTO\TrainingResponse;
use App\Training\Entity\Training;

class TrainingMapper implements TrainingMapperInterface
{
    public function map(Training $training): TrainingResponse
    {
        return TrainingResponse::fromEntity($training);
    }
}
