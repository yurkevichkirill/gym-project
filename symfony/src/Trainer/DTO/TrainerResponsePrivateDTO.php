<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use App\Trainer\Entity\Trainer;
use App\TrainingType\DTO\TrainingTypeResponseDTO;
use LogicException;

final readonly class TrainerResponsePrivateDTO
{
    public function __construct(
        public int                     $id,
        public string                  $firstName,
        public string                  $lastName,
        public string                  $phone,
        public string                  $email,
        public TrainingTypeResponseDTO $trainingType,
        public int                     $pricePerHour,
        public ?string                 $photoPath,
        public ?string                 $education,
        public ?string                 $about,
        public int                     $balance,
        public int                     $debt,
        public string                  $createdAt,
        public string                  $deletedAt,
        public string                  $updatedAt,
        public string                  $blockedAt,
        public string                  $type,
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
            phone: $trainer->getPhone(),
            email: $trainer->getEmail(),
            trainingType:  TrainingTypeResponseDTO::fromEntity($trainingType),
            pricePerHour: $trainer->getPricePerHour(),
            photoPath: $trainer->getPhotoPath(),
            education: $trainer->getEducation(),
            about: $trainer->getAbout(),
            balance: $trainer->getBalance(),
            debt: $trainer->getDebt(),
            createdAt: $trainer->getCreatedAt()?->format(DATE_ATOM) ?? '',
            deletedAt: $trainer->getDeletedAt()?->format(DATE_ATOM) ?? '',
            updatedAt: $trainer->getUpdatedAt()?->format(DATE_ATOM) ?? '',
            blockedAt: $trainer->getBlockedAt()?->format(DATE_ATOM) ?? '',
            type: 'trainer',
        );
    }
}
