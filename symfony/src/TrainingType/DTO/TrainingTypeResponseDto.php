<?php

declare(strict_types=1);

namespace App\TrainingType\DTO;

use App\Training\Entity\Training;
use App\TrainingType\Entity\TrainingType;

final readonly class TrainingTypeResponseDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
    )
    {}

    public static function fromEntity(TrainingType $trainingType): self
    {
        return new self(
            id: $trainingType->getId(),
            name: $trainingType->getName(),
            description: $trainingType->getDescription(),
        );
    }
}
