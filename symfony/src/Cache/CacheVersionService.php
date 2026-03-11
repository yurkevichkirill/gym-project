<?php

declare(strict_types=1);

namespace App\Cache;

use Predis\Client;

final class CacheVersionService
{
    private Client $redis;
    public function __construct()
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host' => 'redis',
            'port' => 6379,
        ]);
    }

    public function bump(string $group): void
    {
        $this->redis->incr("cache_version:$group");
    }
}
