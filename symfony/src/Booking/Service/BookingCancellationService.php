<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Exception\InvalidBookingStatusException;
use App\Infrastructure\ClickHouse\Publisher\AnalyticsPublisher;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Exception\PaymentNotFoundException;
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
        match($booking->getStatus()) {
            BookingStatusEnum::SCHEDULED => $this->cancelScheduled($booking, $actor),
            BookingStatusEnum::PENDING => $this->cancelPending($booking, $actor),
            default => throw new InvalidBookingStatusException(
                sprintf('Booking with status "%s" cannot be canceled', $booking->getStatus()->value)
            ),
        };
    }

    /**
     * @throws Throwable
     */
    private function cancelScheduled(Booking $booking, User $actor): void
    {
        $status = $this->resolveCancellationStatus($actor->getRoles());

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

    /**
     * @throws Throwable
     */
    private function cancelPending(Booking $booking, User $actor): void
    {
        $status = $this->resolveCancellationStatus($actor->getRoles());
        $client = $booking->getClient();
        $payment = $booking->getPayment();
        $training = $booking->getTraining();

        if ($payment === null) {
            throw new InvalidBookingStatusException('Pending booking must have an associated payment');
        }

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

        $paymentId = $payment->getId();

        try {
            $this->entityManager->wrapInTransaction(function () use ($booking, $status, $paymentId) {
                $row = $this->entityManager->getConnection()
                    ->executeQuery(
                        'SELECT id FROM payment WHERE id = :id FOR UPDATE',
                        ['id' => $paymentId]
                    )
                    ->fetchAssociative();

                if (!$row) {
                    throw new PaymentNotFoundException('Payment not found during pending booking cancellation');
                }

                $lockedPayment = $this->entityManager->find(Payment::class, $paymentId);

                if ($lockedPayment === null) {
                    throw new PaymentNotFoundException('Payment not found during pending booking cancellation');
                }

                $this->entityManager->refresh($lockedPayment);

                if ($lockedPayment->getStatus() !== PaymentStatusEnum::PENDING) {
                    throw new InvalidBookingStatusException('Payment is no longer pending. Cancellation aborted.');
                }

                $this->paymentSettlementService->cancelPayment($lockedPayment);

                $booking->setStatus($status);
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

    private function resolveCancellationStatus(array $roles): BookingStatusEnum
    {
        if (in_array(UserRolesEnum::ROLE_CLIENT->value, $roles, true)) {
            $status = BookingStatusEnum::CANCELED_BY_CLIENT;
        } else if (in_array(UserRolesEnum::ROLE_TRAINER->value, $roles, true)) {
            $status = BookingStatusEnum::CANCELED_BY_TRAINER;
        } else {
            $status = BookingStatusEnum::CANCELED_BY_SYSTEM;
        }

        return $status;
    }
}
