<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateTrainerRequestDTO
{
    public function __construct(
        #[Assert\Regex(
            pattern: '/^\+?[1-9]\d{4,14}$/'
        )]
        public ?string $phone = null,
        #[Assert\Positive]
        public ?int $pricePerHour = null,
    )
    {}
}
