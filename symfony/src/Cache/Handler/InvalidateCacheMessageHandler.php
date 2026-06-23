<?php

declare(strict_types=1);

namespace App\Cache\Handler;

use App\Cache\CacheVersionService;
use App\Cache\Message\InvalidateCacheMessage;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler]
final readonly class InvalidateCacheMessageHandler
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheVersionService $cacheVersionService,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(InvalidateCacheMessage $message): void
    {
        if ($message->tags !== []) {
            $this->cache->invalidateTags($message->tags);
        }

        foreach ($message->groups as $group) {
            $this->cacheVersionService->bump($group);
        }
    }
}
