<?php

declare(strict_types=1);

namespace App\Training\Mapper;

use App\Training\DTO\TrainingResponseDTO;
use App\Training\Entity\Training;

final readonly class TrainingMapper implements TrainingMapperInterface
{
    public function map(Training $training): TrainingResponseDTO
    {
        return TrainingResponseDTO::fromEntity($training);
    }
}
