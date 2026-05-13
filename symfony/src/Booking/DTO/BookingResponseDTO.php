<?php

declare(strict_types=1);

namespace App\Booking\DTO;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Payment\DTO\PaymentResponse;

readonly class BookingResponseDTO
{
    public function __construct(
        public int $id,
        public int $trainerId,
        public ?string $bookedAt,
        public string $date,
        public int $durationMinutes,
        public string $startTime,
        public BookingStatusEnum $status,
        public PaymentResponse $payment,
    )
    {}

    public static function fromEntity(Booking $b): self
    {
        return new self(
            id: $b->getId(),
            trainerId: $b->getTraining()->getTrainerWorkTime()->getTrainer()->getId(),
            bookedAt: $b->getBookedAt()?->format(DATE_ATOM) ?? '',
            date: $b->getTraining()->getTrainerWorkTime()->getDate()->format("Y-m-d"),
            durationMinutes: $b->getTraining()->getDurationMinutes(),
            startTime: $b->getTraining()->getStartTime()->format("H:i:s"),
            status: $b->getStatus(),
            payment: PaymentResponse::fromEntity($b->getPayment()),
        );
    }
}
