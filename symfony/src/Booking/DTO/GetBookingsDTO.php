<?php

declare(strict_types=1);

namespace App\Booking\DTO;

final readonly class GetBookingsDTO
{
    public function __construct(
        public array            $sort,
        public BookingFilterDTO $filter,
        public int              $page = 1,
        public int              $limit = 20,
    ) {}
}
