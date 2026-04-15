<?php

declare(strict_types=1);

namespace App\Training\DTO;

use App\Booking\Enum\BookingStatusEnum;
use App\Payment\DTO\PaymentTrainerResponse;
use App\Training\Entity\Training;

final readonly class TrainingResponse
{
    public function __construct(
        public int $id,
        public string $startTime,
        public int $durationMinutes,
        public string $date,
        public int $clientId,
        public string $bookedAt,
        public BookingStatusEnum $status,
        public PaymentTrainerResponse $payment,
    )
    {}

    public static function fromEntity(Training $training): self
    {
        return new self(
            id: $training->getId(),
            startTime: $training->getStartTime()->format("H:i:s"),
            durationMinutes: $training->getDurationMinutes(),
            date: $training->getTrainerWorkTime()->getDate()->format("Y-m-d"),
            clientId: $training->getBooking()->getClient()->getId(),
            bookedAt: $training->getBooking()->getBookedAt()?->format(DATE_ATOM) ?? '',
            status: $training->getBooking()->getStatus(),
            payment: PaymentTrainerResponse::fromEntity($training->getBooking()->getPayment()),
        );
    }
}
