<?php

declare(strict_types=1);

namespace App\Booking\DTO;

use App\Booking\Enum\BookingStatusEnum;

final readonly class GetClientBookings
{
    public function __construct(
        public ?int $clientId,
        public string $sort = 'bookedAt:ASC',
        public ?BookingStatusEnum $status = null,
        public int $page = 1,
        public int $limit = 20
    )
    {}
}
