<?php

declare(strict_types=1);

namespace App\Training\Service;

use App\Enum\DayOfWeekEnum;
use DateTimeImmutable;

interface TrainingServiceInterface
{
    public function findBy(int $trainerId, array $sort, ?DateTimeImmutable $date): array;
    public function generateCacheKey(int $trainerId, array $sort, ?DateTimeImmutable $date): string;
}
