<?php

declare(strict_types=1);

namespace App\Response;

final readonly class OkResponse
{
    public function __construct(
        public mixed $data,
        public int $page = 1,
        public int $limit = 1,
        public array $filter = [],
        public array $sort = [],
        public int $status = 200
    )
    {}
}
