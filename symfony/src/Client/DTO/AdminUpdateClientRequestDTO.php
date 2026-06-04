<?php

declare(strict_types=1);

namespace App\Client\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AdminUpdateClientRequestDTO
{
    public function __construct(
        public ?int $age = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        #[Assert\Email]
        public ?string $email,
        #[Assert\Regex(
            pattern: '/^\+?[1-9]\d{4,14}$/'
        )]
        public ?string $phone,
        #[Assert\PasswordStrength(
            minScore: Assert\PasswordStrength::STRENGTH_MEDIUM,
        )]
        public ?string $password,
    )
    {}
}
