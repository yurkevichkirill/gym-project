<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Exception\InvalidBookingStatusException;
use App\Infrastructure\ClickHouse\Publisher\AnalyticsPublisher;
use App\Payment\Service\PaymentSettlementService;
use App\User\Entity\User;
use App\User\Enum\UserRolesEnum;
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

    /**
     * @throws Throwable
     */
    public function cancel(Booking $booking, User $actor): void
    {
        if ($booking->getStatus() !== BookingStatusEnum::SCHEDULED) {
            throw new InvalidBookingStatusException('Only scheduled bookings can be canceled');
        }

        $roles = $actor->getRoles();
        if (in_array(UserRolesEnum::ROLE_CLIENT->value, $roles, true)) {
            $status = BookingStatusEnum::CANCELED_BY_CLIENT;
        } else if (in_array(UserRolesEnum::ROLE_TRAINER->value, $roles, true)) {
            $status = BookingStatusEnum::CANCELED_BY_TRAINER;
        } else {
            $status = BookingStatusEnum::CANCELED_BY_SYSTEM;
        }

        $client = $booking->getClient();
        $payment = $booking->getPayment();
        $training = $booking->getTraining();

        $loggingContext = [
            'booking_id' => $booking->getId(),
            'client_id' => $booking->getClient()->getId(),
        ];

        $analyticalContext = [
            'client_id' => $client->getId(),
            'trainer_id' => $training?->getTrainerWorkTime()?->getTrainer()?->getId(),
            'booking_id' => $booking->getId(),
            'price' => $payment?->getAmount() ?? 0,
            'payment_method' => $payment?->getMethod()->value ?? 'unknown',
        ];

        try {
            $this->entityManager->wrapInTransaction(function () use ($booking, $status, $payment) {
                if ($payment !== null) {
                    $this->paymentSettlementService->refund($payment);
                }

                $booking->setStatus($status);
                $training = $booking->getTraining();
                if ($training !== null) {
                    $training->setIsBusy(false);
                }
            });
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

        try {
            $this->analyticsPublisher->publish(
                'booking.canceled',
                    $analyticalContext,
            );
        } catch (Throwable $e) {
            $this->bookingLogger->error('analytics.publish.failed',
                [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                    'domain' => 'booking',
                    'operation' => 'cancel',
                    'outcome' => 'failed',
                ]
                + $loggingContext
            );
        }
    }
}
