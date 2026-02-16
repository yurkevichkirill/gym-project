<?php

declare(strict_types=1);

namespace App\Trainer\Mapper;

use App\Trainer\DTO\TrainerResponse;
use App\Trainer\Entity\Trainer;

interface TrainerMapperInterface
{
    public function map(Trainer $trainer): TrainerResponse;
}
