<?php

declare(strict_types=1);

namespace App\Client\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateClientRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $age,
        #[Assert\NotBlank]
        public string $firstName,
        #[Assert\NotBlank]
        public string $lastName,
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank]
        public string $phone,
        #[Assert\NotBlank]
        public string $password,
    )
    {}
}
