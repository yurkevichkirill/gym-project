<?php

declare(strict_types=1);

namespace App\Training\DTO;

use App\Booking\Enum\BookingStatusEnum;
use App\Payment\DTO\PaymentTrainerResponseDTO;
use App\Training\Entity\Training;

final readonly class TrainingResponseDTO
{
    public function __construct(
        public int                       $id,
        public string                    $startTime,
        public int                       $durationMinutes,
        public string                    $date,
        public bool                      $isBusy,
        public int                       $clientId,
        public string                    $bookedAt,
        public BookingStatusEnum         $status,
        public PaymentTrainerResponseDTO $payment,
    )
    {}

    public static function fromEntity(Training $training): self
    {
        return new self(
            id: $training->getId(),
            startTime: $training->getStartTime()->format("H:i:s"),
            durationMinutes: $training->getDurationMinutes(),
            date: $training->getTrainerWorkTime()->getDate()->format("Y-m-d"),
            isBusy: $training->isBusy(),
            clientId: $training->getBooking()->getClient()->getId(),
            bookedAt: $training->getBooking()->getBookedAt()?->format(DATE_ATOM) ?? '',
            status: $training->getBooking()->getStatus(),
            payment: PaymentTrainerResponseDTO::fromEntity($training->getBooking()->getPayment()),
        );
    }
}
