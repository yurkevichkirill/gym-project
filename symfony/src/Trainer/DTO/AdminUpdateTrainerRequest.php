<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AdminUpdateTrainerRequest
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
        public ?int $balance,
    )
    {}
}
