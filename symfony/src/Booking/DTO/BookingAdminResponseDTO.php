<?php

declare(strict_types=1);

namespace App\Booking\DTO;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Payment\DTO\PaymentResponseDTO;
use LogicException;

final readonly class BookingAdminResponseDTO
{
    public function __construct(
        public int                $id,
        public int                $clientId,
        public int                $trainerId,
        public string             $bookedAt,
        public string             $date,
        public int                $durationMinutes,
        public string             $startTime,
        public BookingStatusEnum  $status,
        public PaymentResponseDTO $payment,
    )
    {}

    public static function fromEntity(Booking $b): self
    {
        $training = $b->getTraining();
        $payment = $b->getPayment();
        $bookingId = $b->getId();
        $clientId = $b->getClient()->getId();

        if ($training === null || $payment === null || $bookingId === null || $clientId === null) {
            throw new LogicException('Booking is not fully initialized.');
        }

        return new self(
            id: $bookingId,
            clientId: $clientId,
            trainerId: $training->getTrainerWorkTime()->getTrainer()->getId() ?? throw new LogicException('Trainer is not persisted.'),
            bookedAt: $b->getBookedAt()?->format(DATE_ATOM) ?? '',
            date: $training->getTrainerWorkTime()->getDate()->format('Y-m-d'),
            durationMinutes: $training->getDurationMinutes(),
            startTime: $training->getStartTime()->format('H:i:s'),
            status: $b->getStatus(),
            payment: PaymentResponseDTO::fromEntity($payment),
        );
    }
}
