<?php

declare(strict_types=1);

namespace App\Client\DTO;

final readonly class ClientFilter
{
    public function __construct(
        public ?int $minAge,
        public ?int $maxAge,
        public ?int $minBalance,
        public ?int $maxBalance,
        public ?bool $isDeleted,
    )
    {}
}
