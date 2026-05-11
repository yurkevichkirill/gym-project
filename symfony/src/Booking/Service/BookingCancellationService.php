<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Exception\InvalidBookingStatusException;
use App\Infrastructure\ClickHouse\Publisher\AnalyticsPublisher;
use App\Payment\Service\PaymentSettlementService;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class BookingCancellationService
{
    public function __construct(
        private PaymentSettlementService $paymentSettlementService,
        private EntityManagerInterface $entityManager,
        private AnalyticsPublisher $analyticsPublisher,
        private LoggerInterface $bookingLogger,
    )
    {}

    public function cancel(Booking $booking, User $actor): void
    {
        $roles = $actor->getRoles();
        if (in_array('ROLE_CLIENT', $roles)) {
            if ($booking->getStatus() !== BookingStatusEnum::SCHEDULED) {
                throw new InvalidBookingStatusException("Only scheduled bookings can be canceled by client");
            }

            $status = BookingStatusEnum::CANCELED_BY_CLIENT;
        } else if (in_array('ROLE_TRAINER', $roles)) {
            $status = BookingStatusEnum::CANCELED_BY_TRAINER;
        } else {
            $status = BookingStatusEnum::CANCELED_BY_SYSTEM;
        }

        $loggingContext = [
            'booking_id' => $booking->getId(),
            'client_id' => $booking->getClient()?->getId(),
        ];

        $analyticalContext = [
            'client_id' => $booking->getClient()->getId(),
            'trainer_id' => $booking->getTraining()->getTrainerWorkTime()->getTrainer()->getId(),
            'booking_id' => $booking->getId(),
            'price' => $booking->getPayment()->getAmount(),
            'payment_method' => $booking->getPayment()->getMethod()->value ?? 'unknown',
        ];

        $this->entityManager->wrapInTransaction(function () use ($booking, $status, $loggingContext, $analyticalContext) {
            try {
                $payment = $booking->getPayment();

                $this->paymentSettlementService->refund($payment);

                $booking->cancel($status);

                $this->entityManager->flush();

                $this->analyticsPublisher->publish(
                    'booking.canceled',
                    $analyticalContext,
                );

            } catch (Throwable $e) {
                $this->bookingLogger->error('cancel.failed',
                    [
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                        'domain' => 'booking',
                        'operation' => 'cancel',
                        'outcome' => 'failed',
                    ]
                    + $loggingContext
                );

                throw $e;
            }
        });
    }
}
