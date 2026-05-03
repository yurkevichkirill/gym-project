<?php

declare(strict_types=1);

namespace App\Client\DTO;

final readonly class ClientActivateRequest
{
    public function __construct(
        public string $activationToken,
        public string $password,
    )
    {}
}
