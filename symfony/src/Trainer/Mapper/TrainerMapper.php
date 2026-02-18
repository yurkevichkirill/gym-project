<?php

declare(strict_types=1);

namespace App\Trainer\Mapper;

use App\Trainer\DTO\TrainerResponse;
use App\Trainer\DTO\TrainerResponsePrivate;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;

class TrainerMapper implements TrainerMapperInterface
{
    public function map(Trainer $trainer, bool $private = false): TrainerResponse|TrainerResponsePrivate
    {
        return $private ? TrainerResponsePrivate::fromEntity($trainer) : TrainerResponse::fromEntity($trainer);
    }
}
