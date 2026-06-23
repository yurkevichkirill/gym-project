<?php

declare(strict_types=1);

namespace App\Tests\Cache;

use App\Cache\CacheVersionService;
use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;

final class CacheVersionServiceTest extends TestCase
{
    public function testBumpIncrementsConfiguredCacheGroupVersion(): void
    {
        $redis = $this->createMock(ClientInterface::class);
        $redis
            ->expects(self::once())
            ->method('__call')
            ->with('incr', ['cache_version:trainers']);

        (new CacheVersionService($redis))->bump('trainers');
    }
}
