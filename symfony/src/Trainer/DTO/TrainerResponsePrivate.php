<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use App\Trainer\Entity\Trainer;

final readonly class TrainerResponsePrivate
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $phone,
        public string $email,
        public int $trainingTypeId,
        public string $price,
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
            trainingTypeId:  $trainer->getTrainingType()->getId(),
            price: $trainer->getPrice(),
        );
    }
}
