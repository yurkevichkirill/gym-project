<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Service;

interface TrainerWorkTimeServiceInterface
{
    public function findBy(int $id, array $sort, ?\DateTimeImmutable $date): array;
    public function generateCacheKey(int $id, array $sort, ?\DateTimeImmutable $date): string;
}
