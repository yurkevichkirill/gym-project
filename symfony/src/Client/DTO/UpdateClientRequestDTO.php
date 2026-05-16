<?php

declare(strict_types=1);

namespace App\Client\DTO;

final readonly class UpdateClientRequestDTO
{
    public function __construct(
        public ?string $phone = null
    )
    {}
}
