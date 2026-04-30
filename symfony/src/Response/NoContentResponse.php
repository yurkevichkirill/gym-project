<?php

declare(strict_types=1);

namespace App\Response;

final readonly class NoContentResponse
{
    public function __construct(
        public int $status = 204,
    )
    {}
}
