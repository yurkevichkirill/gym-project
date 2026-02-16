<?php

declare(strict_types=1);

namespace App\Trainer\Mapper;

use App\Trainer\DTO\TrainerResponse;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;

class TrainerMapper implements TrainerMapperInterface
{
    public function map(Trainer $trainer): TrainerResponse
    {
        return TrainerResponse::fromEntity($trainer);
    }
}
