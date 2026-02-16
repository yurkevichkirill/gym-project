<?php

declare(strict_types=1);

namespace App\Client\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateClientRequest
{
    public function __construct(
        public int $age,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public string $password,
    )
    {}
}
