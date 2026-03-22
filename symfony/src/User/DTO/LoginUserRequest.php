<?php

declare(strict_types=1);

namespace App\User\DTO;

final readonly class LoginUserRequest
{
    public function __construct(
        public string $email,
        public string $password,
    )
    {}
}
