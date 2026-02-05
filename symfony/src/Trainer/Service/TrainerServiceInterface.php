<?php

declare(strict_types=1);

namespace App\Trainer\Service;

use App\Enum\DayOfWeekEnum;
use App\Trainer\Entity\Trainer;
use Doctrine\ORM\EntityManagerInterface;

interface TrainerServiceInterface
{
    public function getAvailable(Trainer $trainer, DayOfWeekEnum $dayOfWeek): array;
    public function findBy(array $sort, ?int $trainingTypeId): array;
    public function generateCacheKey(array $sort, ?int $trainingTypeId): string;
}
