<?php

declare(strict_types=1);

namespace App\Booking\DTO;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;

readonly class BookingResponse
{
    public function __construct(
        public int $id,
        public int $trainingId,
        public string $bookedAt,
        public BookingStatusEnum $status
    )
    {}

    public static function fromEntity(Booking $b): self
    {
        return new self(
            id: $b->getId(),
            trainingId: $b->getTraining()->getId(),
            bookedAt: $b->getBookedAt()?->format(DATE_ATOM) ??'',
            status: $b->getStatus(),
        );
    }
}
