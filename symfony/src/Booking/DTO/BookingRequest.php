<?php

declare(strict_types=1);

namespace App\Booking\DTO;

use App\Booking\Validator\MultipleOf30;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class BookingRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[Assert\GreaterThanOrEqual(30)]
        #[MultipleOf30]
        public int $durationMinutes,
        #[Assert\Time]
        public string $startTime,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $trainerId,
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date,
    )
    {}
}
