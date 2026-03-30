<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use App\Trainer\Entity\Trainer;
use App\TrainingType\DTO\TrainingTypeResponseDto;

final readonly class TrainerResponse
{
    public function __construct(
        public int    $id,
        public string $firstName,
        public string $lastName,
        public TrainingTypeResponseDto    $trainingType,
        public string $pricePerHour,
        public string $photoUrl,
        public string $education,
        public string $about,
    )
    {}

    public static function fromEntity(Trainer $trainer): self
    {
        return new self(
            id: $trainer->getId(),
            firstName: $trainer->getFirstName(),
            lastName: $trainer->getLastName(),
            trainingType:  TrainingTypeResponseDto::fromEntity($trainer->getTrainingType()),
            pricePerHour: $trainer->getPricePerHour(),
            photoUrl: $trainer->getPhotoUrl(),
            education: $trainer->getEducation(),
            about: $trainer->getAbout(),
        );
    }
}
