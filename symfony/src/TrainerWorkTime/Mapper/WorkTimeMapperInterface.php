<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Mapper;

use App\TrainerWorkTime\DTO\WorkTimeResponse;
use App\TrainerWorkTime\Entity\TrainerWorkTime;

interface WorkTimeMapperInterface
{
    public function map(TrainerWorkTime $worktime): WorkTimeResponse;
}
