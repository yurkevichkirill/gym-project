<?php

declare(strict_types=1);

namespace App\Booking\Mapper;

use App\Booking\DTO\BookingAdminResponseDTO;
use App\Booking\Entity\Booking;

final readonly class BookingAdminMapper implements BookingAdminMapperInterface
{
    public function map(Booking $booking): BookingAdminResponseDTO
    {
        return BookingAdminResponseDTO::fromEntity($booking);
    }
}
