<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateTrainerRequest
{
    public function __construct(
        #[Assert\Email]
        public ?string $email = null,
        public ?string $phone = null,
        #[Assert\Positive]
        public ?string $price = null,
    )
    {}
}
