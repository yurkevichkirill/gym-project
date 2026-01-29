<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Trainer;
use App\Enum\DayOfWeekEnum;

interface TrainerServiceInterface
{
    public function getAvailable(Trainer $trainer, DayOfWeekEnum $dayOfWeek): array;
    public function getScheduled(Trainer $trainer, DayOfWeekEnum $dayOfWeek): array;
}
