<?php

declare(strict_types=1);

namespace App\TrainingType\Mapper;

use App\TrainingType\DTO\TrainingTypeResponseDTO;
use App\TrainingType\Entity\TrainingType;

interface TrainingTypeMapperInterface
{
    public function map (TrainingType $trainingType): TrainingTypeResponseDTO;
}
