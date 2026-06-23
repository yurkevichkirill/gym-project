<?php

declare(strict_types=1);

namespace App\Cache;

use Predis\ClientInterface;

final readonly class CacheVersionService
{
    public function __construct(
        private ClientInterface $redis,
    ) {}

    public function bump(string $group): void
    {
        $this->redis->incr("cache_version:$group");
    }
}
