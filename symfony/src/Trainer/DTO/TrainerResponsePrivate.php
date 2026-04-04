<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use App\Trainer\Entity\Trainer;
use App\TrainingType\DTO\TrainingTypeResponseDto;

final readonly class TrainerResponsePrivate
{
    public function __construct(
        public int    $id,
        public string $firstName,
        public string $lastName,
        public string $phone,
        public string $email,
        public TrainingTypeResponseDto $trainingType,
        public string $pricePerHour,
        public string $photoUrl,
        public string $education,
        public string $about,
        public string $type,
    )
    {}

    public static function fromEntity(Trainer $trainer): self
    {
        return new self(
            id: $trainer->getId(),
            firstName: $trainer->getFirstName(),
            lastName: $trainer->getLastName(),
            phone: $trainer->getPhone(),
            email: $trainer->getEmail(),
            trainingType:  TrainingTypeResponseDto::fromEntity($trainer->getTrainingType()),
            pricePerHour: $trainer->getPricePerHour(),
            photoUrl: $trainer->getPhotoUrl(),
            education: $trainer->getEducation(),
            about: $trainer->getAbout(),
            type: 'trainer',
        );
    }
}
