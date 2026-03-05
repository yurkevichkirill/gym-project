<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;

final readonly class TrainerResponse
{
    public function __construct(
        public int    $id,
        public string $firstName,
        public string $lastName,
        public int    $trainingTypeId,
        public string $pricePerHour,
    )
    {}

    public static function fromEntity(Trainer $trainer): self
    {
        return new self(
            id: $trainer->getId(),
            firstName: $trainer->getFirstName(),
            lastName: $trainer->getLastName(),
            trainingTypeId:  $trainer->getTrainingType()->getId(),
            pricePerHour: $trainer->getPricePerHour(),
        );
    }
}
