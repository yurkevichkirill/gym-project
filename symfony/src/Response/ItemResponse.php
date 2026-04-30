<?php

declare(strict_types=1);

namespace App\Response;

final readonly class ItemResponse
{
    public function __construct(
        public mixed $data,
        public int $status = 200,
    )
    {}
}
