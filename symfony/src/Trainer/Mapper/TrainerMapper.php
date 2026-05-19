<?php

declare(strict_types=1);

namespace App\Trainer\Mapper;

use App\Trainer\DTO\TrainerResponseDTO;
use App\Trainer\DTO\TrainerResponsePrivateDTO;
use App\Trainer\Entity\Trainer;

final readonly class TrainerMapper implements TrainerMapperInterface
{
    public function map(Trainer $trainer, bool $private = false): TrainerResponseDTO|TrainerResponsePrivateDTO
    {
        return $private ? TrainerResponsePrivateDTO::fromEntity($trainer) : TrainerResponseDTO::fromEntity($trainer);
    }
}
