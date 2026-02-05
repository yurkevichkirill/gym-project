<?php

declare(strict_types=1);

namespace App\Client\Service;

interface ClientServiceInterface
{
    public function findBy(array $sort): array;
    public function generateCacheKey(array $sort): string;
}
