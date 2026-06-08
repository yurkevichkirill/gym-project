<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTrainerRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public string $firstName,
        #[Assert\NotBlank]
        public string $lastName,
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Regex(
            pattern: '/^\+?[1-9]\d{4,14}$/'
        )]
        public string $phone,
        #[Assert\NotBlank]
        #[Assert\PasswordStrength(
            minScore: Assert\PasswordStrength::STRENGTH_MEDIUM,
        )]
        public string $password,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $trainingTypeId,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $pricePerHour,
        public ?string $education,
        public ?string $about,
    )
    {}
}
