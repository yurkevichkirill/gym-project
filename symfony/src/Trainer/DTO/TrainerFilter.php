<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use App\TrainingType\Entity\TrainingType;

final readonly class TrainerFilter
{
    public function __construct(
        public ?int $minPricePerHour,
        public ?int $maxPricePerHour,
        public ?TrainingType $trainingType,
    ) {}
}
