<?php

declare(strict_types=1);

namespace App\Booking\DTO;

use App\Booking\Validator\MultipleOf;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class BookingRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[Assert\GreaterThanOrEqual(30)]
        #[MultipleOf(multiple: 30)]
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
