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
        public string $email,
        #[Assert\NotBlank]
        public string $phone,
        #[Assert\NotBlank]
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
