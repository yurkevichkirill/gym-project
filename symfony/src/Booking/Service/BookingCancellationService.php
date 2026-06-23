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
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class BookingCancellationService
{
    private const string CLIENT_CANCELLATION_WINDOW = 'PT2H';

    public function __construct(
        private PaymentSettlementService $paymentSettlementService,
        private EntityManagerInterface $entityManager,
        private AnalyticsPublisher $analyticsPublisher,
        private LoggerInterface $bookingLogger,
    ) {}

    /**
     * @throws Throwable
     */
    public function cancel(Booking $booking, User $actor): void
    {
        match ($booking->getStatus()) {
            BookingStatusEnum::SCHEDULED => $this->cancelScheduled($booking, $actor),
            BookingStatusEnum::PENDING => $this->cancelPending($booking, $actor),
            default => throw new InvalidBookingStatusException(
                sprintf('Booking with status "%s" cannot be canceled', $booking->getStatus()->value),
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

        $bookingId = $booking->getId();
        if ($bookingId === null) {
            throw new InvalidBookingStatusException('Scheduled booking must be persisted');
        }

        $paymentId = $payment?->getId();
        if ($payment !== null && $paymentId === null) {
            throw new PaymentNotFoundException('Scheduled booking payment must be persisted');
        }

        $cancelLockedBooking = function (?Payment $lockedPayment) use (
            $bookingId,
            $actor,
            $status,
        ): void {
            $lockedBooking = $this->lockBooking($bookingId);

            $this->assertScheduledBookingCanBeCanceled($lockedBooking);
            $this->assertClientCanCancelScheduledBooking($lockedBooking, $actor);

            if (
                $lockedPayment !== null
                && $lockedBooking->getPayment()?->getId() !== $lockedPayment->getId()
            ) {
                throw new PaymentNotFoundException('Booking payment changed during cancellation');
            }

            if ($lockedPayment !== null) {
                $refundMessage = $this->paymentSettlementService->refund($lockedPayment);
                $this->paymentSettlementService->dispatchPaymentMessage($refundMessage);
            }

            $lockedBooking->setStatus($status);
            $lockedBooking->getTraining()?->setIsBusy(false);
        };

        try {
            if ($paymentId !== null) {
                $this->paymentSettlementService->withLockedPayment(
                    $paymentId,
                    $cancelLockedBooking,
                );
            } else {
                $this->entityManager->wrapInTransaction(
                    fn (): mixed => $cancelLockedBooking(null),
                );
            }
        } catch (Throwable $e) {
            $this->bookingLogger->error(
                'cancel.failed',
                [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                    'domain' => 'booking',
                    'operation' => 'cancel',
                    'outcome' => 'failed',
                ] + $loggingContext,
            );

            throw $e;
        }

        try {
            $this->analyticsPublisher->publish(
                'booking.canceled',
                $analyticalContext,
            );
        } catch (Throwable $e) {
            $this->bookingLogger->error(
                'analytics.publish.failed',
                [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                    'domain' => 'booking',
                    'operation' => 'cancel',
                    'outcome' => 'failed',
                ] + $loggingContext,
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
            'price' => $payment->getAmount(),
            'payment_method' => $payment->getMethod()->value,
        ];

        $paymentId = $payment->getId();
        if ($paymentId === null) {
            throw new InvalidBookingStatusException('Pending booking payment must be persisted');
        }

        try {
            $this->paymentSettlementService->withLockedPayment(
                $paymentId,
                function (Payment $lockedPayment) use ($booking, $status, $training): void {
                    if ($lockedPayment->getStatus() !== PaymentStatusEnum::PENDING) {
                        throw new InvalidBookingStatusException(
                            'Payment is no longer pending. Cancellation aborted.',
                        );
                    }

                    $message = $this->paymentSettlementService->cancelPayment($lockedPayment);

                    $training?->setIsBusy(false);
                    $booking->setStatus($status);

                    $this->paymentSettlementService->dispatchPaymentMessage($message);
                },
            );
        } catch (Throwable $e) {
            $this->bookingLogger->error(
                'cancel.failed',
                [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                    'domain' => 'booking',
                    'operation' => 'cancel',
                    'outcome' => 'failed',
                ] + $loggingContext,
            );

            throw $e;
        }

        try {
            $this->analyticsPublisher->publish(
                'booking.canceled',
                $analyticalContext,
            );
        } catch (Throwable $e) {
            $this->bookingLogger->error(
                'analytics.publish.failed',
                [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                    'domain' => 'booking',
                    'operation' => 'cancel',
                    'outcome' => 'failed',
                ] + $loggingContext,
            );
        }
    }

    private function assertScheduledBookingCanBeCanceled(Booking $booking): void
    {
        if ($booking->getStatus() !== BookingStatusEnum::SCHEDULED) {
            throw new InvalidBookingStatusException(
                sprintf('Booking with status "%s" cannot be canceled', $booking->getStatus()->value),
            );
        }
    }

    private function lockBooking(int $bookingId): Booking
    {
        $this->entityManager->getConnection()->executeStatement(
            'SELECT id FROM booking WHERE id = :id FOR UPDATE',
            ['id' => $bookingId],
        );

        $lockedBooking = $this->entityManager->find(Booking::class, $bookingId);
        if ($lockedBooking === null) {
            throw new InvalidBookingStatusException('Booking not found');
        }

        $this->entityManager->refresh($lockedBooking);

        return $lockedBooking;
    }

    private function assertClientCanCancelScheduledBooking(Booking $booking, User $actor): void
    {
        if (!$this->isClientActor($actor)) {
            return;
        }

        $training = $booking->getTraining();
        if ($training === null) {
            throw new InvalidBookingStatusException('Scheduled booking must have an associated training');
        }

        $trainingStart = new DateTimeImmutable(sprintf(
            '%s %s',
            $training->getTrainerWorkTime()->getDate()->format('Y-m-d'),
            $training->getStartTime()->format('H:i:s'),
        ));

        $cancellationDeadline = $trainingStart->sub(new DateInterval(self::CLIENT_CANCELLATION_WINDOW));

        if (new DateTimeImmutable() >= $cancellationDeadline) {
            throw new InvalidBookingStatusException('Client cancellation deadline has passed');
        }
    }

    private function isClientActor(User $actor): bool
    {
        return in_array(UserRolesEnum::ROLE_CLIENT->value, $actor->getRoles(), true);
    }

    /**
     * @param array<string> $roles
     */
    private function resolveCancellationStatus(array $roles): BookingStatusEnum
    {
        if (in_array(UserRolesEnum::ROLE_CLIENT->value, $roles, true)) {
            $status = BookingStatusEnum::CANCELED_BY_CLIENT;
        } elseif (in_array(UserRolesEnum::ROLE_TRAINER->value, $roles, true)) {
            $status = BookingStatusEnum::CANCELED_BY_TRAINER;
        } else {
            $status = BookingStatusEnum::CANCELED_BY_SYSTEM;
        }

        return $status;
    }
}
