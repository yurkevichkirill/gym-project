<?php

declare(strict_types=1);

namespace App\TrainingType\Mapper;

use App\TrainingType\DTO\TrainingTypeResponseDTO;
use App\TrainingType\Entity\TrainingType;
use App\TrainingType\Mapper\TrainingTypeMapperInterface;

class TrainingTypeMapper implements TrainingTypeMapperInterface
{

    public function map(TrainingType $trainingType): TrainingTypeResponseDTO
    {
        return TrainingTypeResponseDTO::fromEntity($trainingType);
    }
}
