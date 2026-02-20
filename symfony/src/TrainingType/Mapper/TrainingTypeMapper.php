<?php

declare(strict_types=1);

namespace App\TrainingType\Mapper;

use App\TrainingType\DTO\TrainingTypeResponseDto;
use App\TrainingType\Entity\TrainingType;
use App\TrainingType\Mapper\TrainingTypeMapperInterface;

class TrainingTypeMapper implements TrainingTypeMapperInterface
{

    public function map(TrainingType $trainingType): TrainingTypeResponseDto
    {
        return TrainingTypeResponseDto::fromEntity($trainingType);
    }
}
