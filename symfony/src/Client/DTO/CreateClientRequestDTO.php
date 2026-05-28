<?php

declare(strict_types=1);

namespace App\Client\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateClientRequestDTO
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
        #[Assert\Regex(
            pattern: '/^\+?[1-9]\d{4,14}$/'
        )]
        public string $phone,
        #[Assert\PasswordStrength(
            minScore: Assert\PasswordStrength::STRENGTH_MEDIUM,
        )]
        public string $password,
    )
    {}
}
