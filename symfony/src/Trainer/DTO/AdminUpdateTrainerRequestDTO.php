<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AdminUpdateTrainerRequestDTO
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
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
        public ?int $pricePerHour,
        public ?string $education,
        public ?string $about,
    )
    {}
}
