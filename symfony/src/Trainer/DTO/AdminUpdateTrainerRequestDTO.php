<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

final readonly class AdminUpdateTrainerRequestDTO
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $email,
        public ?string $phone,
        public ?string $password,
        public ?int $pricePerHour,
        public ?string $photoUrl,
        public ?string $education,
        public ?string $about,
    )
    {}
}
