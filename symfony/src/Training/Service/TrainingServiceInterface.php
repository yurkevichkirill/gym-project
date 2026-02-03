<?php

declare(strict_types=1);

namespace App\Training\Service;

use App\Enum\DayOfWeekEnum;

interface TrainingServiceInterface
{
    public function findBy(int $trainerId, array $sort, ?\DateTimeImmutable $date): array;
}
