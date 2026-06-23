<?php

declare(strict_types=1);

namespace App\Cache\Message;

final readonly class InvalidateCacheMessage
{
    /**
     * @param list<string> $tags
     * @param list<string> $groups
     */
    public function __construct(
        public array $tags,
        public array $groups,
    ) {}
}
