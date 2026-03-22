<?php

declare(strict_types=1);

namespace App\User\DTO;

final readonly class LoginUserResponse
{
    public function __construct(
        public string $email,
        public string $token,
    )
    {}
}
