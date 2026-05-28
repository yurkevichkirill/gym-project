<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use App\Trainer\Entity\Trainer;
use App\TrainingType\DTO\TrainingTypeResponseDTO;
use LogicException;

final readonly class TrainerResponseDTO
{
    public function __construct(
        public int                     $id,
        public string                  $firstName,
        public string                  $lastName,
        public TrainingTypeResponseDTO $trainingType,
        public int                     $pricePerHour,
        public string                  $photoPath,
        public ?string                 $education,
        public ?string                 $about,
    )
    {}

    public static function fromEntity(Trainer $trainer): self
    {
        $id = $trainer->getId();
        $trainingType = $trainer->getTrainingType();
        if ($id === null || $trainingType === null) {
            throw new LogicException('Trainer is not fully initialized.');
        }

        return new self(
            id: $id,
            firstName: $trainer->getFirstName(),
            lastName: $trainer->getLastName(),
            trainingType:  TrainingTypeResponseDTO::fromEntity($trainingType),
            pricePerHour: $trainer->getPricePerHour(),
            photoPath: $trainer->getPhotoPath(),
            education: $trainer->getEducation(),
            about: $trainer->getAbout(),
        );
    }
}
