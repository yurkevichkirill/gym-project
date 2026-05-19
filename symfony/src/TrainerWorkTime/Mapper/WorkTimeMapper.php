<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Mapper;

use App\TrainerWorkTime\DTO\WorkTimeResponseDTO;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use DateMalformedIntervalStringException;

class WorkTimeMapper implements WorkTimeMapperInterface
{

    /**
     * @throws DateMalformedIntervalStringException
     */
    public function map(TrainerWorkTime $worktime): WorkTimeResponseDTO
    {
        return WorkTimeResponseDTO::fromEntity($worktime);
    }
}
