<?php

declare(strict_types=1);

namespace App\Booking\Mapper;

use App\Booking\DTO\BookingResponse;
use App\Booking\Entity\Booking;

interface BookingMapperInterface
{
    public function map(Booking $booking): BookingResponse;
}
