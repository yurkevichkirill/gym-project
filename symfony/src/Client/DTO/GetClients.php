<?php

declare(strict_types=1);

namespace App\Client\DTO;

final readonly class GetClients
{
    public function __construct(
        public array $sort,
        public ClientFilter $filter,
        public int $page = 1,
        public int $limit = 20,
    ) {}
}
