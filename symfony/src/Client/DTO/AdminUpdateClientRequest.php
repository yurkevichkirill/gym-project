<?php

declare(strict_types=1);

namespace App\Client\DTO;

final readonly class AdminUpdateClientRequest
{
    public function __construct(
        public ?int $age = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?int $balance = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $password = null,
    )
    {}
}
