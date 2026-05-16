<?php

declare(strict_types=1);

namespace App\User\DTO;

final readonly class LoginUserRequestDTO
{
    public function __construct(
        public string $email,
        public string $password,
    )
    {}
}
