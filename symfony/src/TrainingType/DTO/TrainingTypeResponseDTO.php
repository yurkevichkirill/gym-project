<?php

declare(strict_types=1);

namespace App\TrainingType\DTO;

use App\TrainingType\Entity\TrainingType;
use LogicException;

final readonly class TrainingTypeResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public string $photoUrl,
    )
    {}

    public static function fromEntity(TrainingType $trainingType): self
    {
        $id = $trainingType->getId();
        if ($id === null) {
            throw new LogicException('Training type is not persisted.');
        }

        return new self(
            id: $id,
            name: $trainingType->getName(),
            description: $trainingType->getDescription(),
            photoUrl: $trainingType->getPhotoUrl(),
        );
    }
}
