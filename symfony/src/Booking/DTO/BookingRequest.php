<?php

declare(strict_types=1);

namespace App\Booking\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class BookingRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $trainingId,
    )
    {}
}
