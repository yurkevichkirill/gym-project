<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Mapper;

use App\TrainerWorkTime\DTO\WorkTimeResponse;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use DateMalformedIntervalStringException;

class WorkTimeMapper implements WorkTimeMapperInterface
{

    /**
     * @throws DateMalformedIntervalStringException
     */
    public function map(TrainerWorkTime $worktime): WorkTimeResponse
    {
        return WorkTimeResponse::fromEntity($worktime);
    }
}
