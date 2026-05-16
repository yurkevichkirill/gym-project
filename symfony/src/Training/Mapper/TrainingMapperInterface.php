<?php

declare(strict_types=1);

namespace App\Training\Mapper;

use App\Training\DTO\TrainingResponseDTO;
use App\Training\Entity\Training;

interface TrainingMapperInterface
{
    public function map(Training $training): TrainingResponseDTO;
}
