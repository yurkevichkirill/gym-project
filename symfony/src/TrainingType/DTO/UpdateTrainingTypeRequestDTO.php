<?php

declare(strict_types=1);

namespace App\TrainingType\DTO;

final readonly class UpdateTrainingTypeRequestDTO
{
    public function __construct(
        public ?string $name,
        public ?string $description,
    )
    {}
}
