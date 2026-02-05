<?php

declare(strict_types=1);

namespace App\TrainingType;

use Symfony\Contracts\Cache\TagAwareCacheInterface;

interface TrainingTypeServiceInterface
{
    public function findBy(array $sort): array;
}
