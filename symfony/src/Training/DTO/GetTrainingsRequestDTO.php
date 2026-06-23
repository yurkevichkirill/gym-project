<?php

declare(strict_types=1);

namespace App\Training\DTO;

use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Validator\MultipleOf;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetTrainingsRequestDTO
{
    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $trainerId = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $clientId = null,

        public ?BookingStatusEnum $status = null,

        #[Assert\Date]
        public ?string $date = null,

        #[Assert\Time]
        public ?string $startTime = null,

        #[Assert\Type('integer')]
        #[Assert\GreaterThanOrEqual(30)]
        #[MultipleOf(multiple: 30)]
        public ?int $durationMinutes = null,

        public ?bool $isBusy = null,

        public string $sort = 'bookedAt:ASC',

        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Positive]
        #[Assert\LessThanOrEqual(100)]
        public int $limit = 20,
    ) {}
}
