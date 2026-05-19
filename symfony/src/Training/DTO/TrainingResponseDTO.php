<?php

declare(strict_types=1);

namespace App\Training\DTO;

use App\Booking\Enum\BookingStatusEnum;
use App\Payment\DTO\PaymentTrainerResponseDTO;
use App\Training\Entity\Training;
use LogicException;

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
        $id = $training->getId();
        $booking = $training->getBooking();

        if ($id === null || $booking === null) {
            throw new LogicException('Training is not fully initialized.');
        }

        $clientId = $booking->getClient()->getId();
        $payment = $booking->getPayment();

        if ($clientId === null || $payment === null) {
            throw new LogicException('Training booking is not fully initialized.');
        }

        return new self(
            id: $id,
            startTime: $training->getStartTime()->format('H:i:s'),
            durationMinutes: $training->getDurationMinutes(),
            date: $training->getTrainerWorkTime()->getDate()->format('Y-m-d'),
            isBusy: $training->isBusy(),
            clientId: $clientId,
            bookedAt: $booking->getBookedAt()?->format(DATE_ATOM) ?? '',
            status: $booking->getStatus(),
            payment: PaymentTrainerResponseDTO::fromEntity($payment),
        );
    }
}
