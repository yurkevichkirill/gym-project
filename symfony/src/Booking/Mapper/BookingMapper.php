<?php

declare(strict_types=1);

namespace App\Booking\Mapper;

use App\Booking\DTO\BookingResponseDTO;
use App\Booking\Entity\Booking;

final readonly class BookingMapper implements BookingMapperInterface
{
    public function map(Booking $booking): BookingResponseDTO
    {
        return BookingResponseDTO::fromEntity($booking);
    }
}
