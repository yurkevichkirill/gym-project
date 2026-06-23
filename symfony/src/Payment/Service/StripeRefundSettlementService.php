<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Membership\Enum\MembershipStatusEnum;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Exception\PaymentNotFoundException;
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Entity\Trainer;
use App\Trainer\Exception\TrainerNotFoundException;
use App\User\Exception\UserNotFoundException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class StripeRefundSettlementService
{
    public function __construct(
        private PaymentSettlementService $paymentSettlementService,
        private PaymentService $paymentService,
        private PaymentLifecycleService $paymentLifecycleService,
        private PaymentRepository $paymentRepository,
        private BalanceService $balanceService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $paymentLogger,
    ) {}

    public function markPending(string $intentId): void
    {
        $payment = $this->findPayment($intentId);

        $this->paymentSettlementService->withLockedPayment(
            $this->requirePaymentId($payment),
            function (Payment $lockedPayment): void {
                if (in_array($lockedPayment->getStatus(), [
                    PaymentStatusEnum::SUCCEEDED,
                    PaymentStatusEnum::REFUND_FAILED,
                ], true)) {
                    $this->paymentLifecycleService->transitionTo(
                        $lockedPayment,
                        PaymentStatusEnum::REFUND_PENDING,
                    );
                }
            },
        );
    }

    public function handleSucceeded(string $intentId, ?int $refundAmount = null): void
    {
        $payment = $this->findPayment($intentId);

        $this->paymentSettlementService->withLockedPayment(
            $this->requirePaymentId($payment),
            function (Payment $lockedPayment) use ($intentId, $refundAmount): void {
                if ($lockedPayment->getStatus() === PaymentStatusEnum::REFUNDED) {
                    return;
                }

                if (!in_array($lockedPayment->getStatus(), [
                    PaymentStatusEnum::REFUND_PENDING,
                    PaymentStatusEnum::REFUND_FAILED,
                    PaymentStatusEnum::SUCCEEDED,
                    PaymentStatusEnum::CANCELED,
                    PaymentStatusEnum::FAILED,
                ], true)) {
                    $this->paymentLogger->warning('payment.stripe_refund.ignored', [
                        'intent_id' => $intentId,
                        'payment_id' => $lockedPayment->getId(),
                        'current_status' => $lockedPayment->getStatus()->value,
                    ]);

                    return;
                }

                if ($refundAmount !== null && $refundAmount !== $lockedPayment->getAmount()) {
                    $this->paymentLogger->critical('payment.stripe_refund.partial_unsupported', [
                        'intent_id' => $intentId,
                        'payment_id' => $lockedPayment->getId(),
                        'payment_amount' => $lockedPayment->getAmount(),
                        'refund_amount' => $refundAmount,
                        'action' => 'manual_reconciliation_required',
                    ]);

                    return;
                }

                if (in_array($lockedPayment->getStatus(), [
                    PaymentStatusEnum::REFUND_PENDING,
                    PaymentStatusEnum::REFUND_FAILED,
                    PaymentStatusEnum::SUCCEEDED,
                ], true)) {
                    $this->reverseSettledPaymentEffects($lockedPayment);
                }

                $this->createRefundPaymentRecord($lockedPayment);
                $this->paymentLifecycleService->transitionTo($lockedPayment, PaymentStatusEnum::REFUNDED);
            },
        );
    }

    public function handleFailed(string $intentId): void
    {
        $payment = $this->findPayment($intentId);

        $this->paymentSettlementService->withLockedPayment(
            $this->requirePaymentId($payment),
            function (Payment $lockedPayment) use ($intentId): void {
                if (in_array($lockedPayment->getStatus(), [
                    PaymentStatusEnum::REFUNDED,
                    PaymentStatusEnum::REFUND_FAILED,
                ], true)) {
                    return;
                }

                if ($lockedPayment->getStatus() === PaymentStatusEnum::REFUND_PENDING) {
                    $this->paymentLifecycleService->transitionTo(
                        $lockedPayment,
                        PaymentStatusEnum::REFUND_FAILED,
                    );

                    $this->paymentLogger->critical('payment.stripe_refund.failed', [
                        'intent_id' => $intentId,
                        'payment_id' => $lockedPayment->getId(),
                        'current_status' => $lockedPayment->getStatus()->value,
                        'action' => 'manual_reconciliation_required',
                    ]);

                    return;
                }

                if (in_array($lockedPayment->getStatus(), [
                    PaymentStatusEnum::CANCELED,
                    PaymentStatusEnum::FAILED,
                ], true)) {
                    $this->paymentLogger->critical('payment.stripe_refund.reconciliation_failed', [
                        'intent_id' => $intentId,
                        'payment_id' => $lockedPayment->getId(),
                        'current_status' => $lockedPayment->getStatus()->value,
                        'action' => 'manual_reconciliation_required',
                    ]);

                    return;
                }

                $this->paymentLogger->warning('payment.stripe_refund.failure_ignored', [
                    'intent_id' => $intentId,
                    'payment_id' => $lockedPayment->getId(),
                    'current_status' => $lockedPayment->getStatus()->value,
                ]);
            },
        );
    }

    public function handleActionRequired(string $intentId): void
    {
        $payment = $this->findPayment($intentId);

        if ($payment->getStatus() === PaymentStatusEnum::REFUNDED) {
            return;
        }

        $this->paymentLogger->critical('payment.stripe_refund.action_required', [
            'intent_id' => $intentId,
            'payment_id' => $payment->getId(),
            'current_status' => $payment->getStatus()->value,
            'action' => 'manual_reconciliation_required',
        ]);
    }

    public function handleDisputeCreated(string $intentId): void
    {
        $payment = $this->findPayment($intentId);

        $this->paymentLogger->critical('payment.stripe_dispute.created', [
            'intent_id' => $intentId,
            'payment_id' => $payment->getId(),
            'current_status' => $payment->getStatus()->value,
            'action' => 'manual_dispute_review_required',
        ]);
    }

    public function handleDisputeFundsReinstated(string $intentId): void
    {
        $payment = $this->findPayment($intentId);

        $this->paymentLogger->info('payment.stripe_dispute.funds_reinstated', [
            'intent_id' => $intentId,
            'payment_id' => $payment->getId(),
            'current_status' => $payment->getStatus()->value,
        ]);
    }

    private function reverseSettledPaymentEffects(Payment $payment): void
    {
        if ($payment->getCategory() === PaymentCategoryEnum::BALANCE_TOP_UP) {
            $this->reverseTopUpCredit($payment);
        } else {
            $this->reverseTrainerPayout($payment);
        }

        $booking = $payment->getBooking();
        if ($booking !== null) {
            if (in_array($booking->getStatus(), [
                BookingStatusEnum::PENDING,
                BookingStatusEnum::SCHEDULED,
            ], true)) {
                $booking->setStatus(BookingStatusEnum::CANCELED_BY_SYSTEM);
                $booking->getTraining()?->setIsBusy(false);
            } elseif ($booking->getStatus() === BookingStatusEnum::COMPLETED) {
                $this->paymentLogger->critical('payment.stripe_refund.completed_booking', [
                    'payment_id' => $payment->getId(),
                    'booking_id' => $booking->getId(),
                    'action' => 'manual_reconciliation_required',
                ]);
            }
        }

        $membership = $payment->getMembership();
        if ($membership !== null && in_array($membership->getStatus(), [
            MembershipStatusEnum::ACTIVE,
            MembershipStatusEnum::FROZEN,
            MembershipStatusEnum::PENDING,
        ], true)) {
            $membership->cancel(MembershipStatusEnum::CANCELED_BY_SYSTEM);
        }
    }

    private function reverseTopUpCredit(Payment $payment): void
    {
        $clientId = $payment->getClient()?->getId();
        if ($clientId === null) {
            throw new UserNotFoundException('Payment client was not found');
        }

        $client = $this->findClientForUpdate($clientId);
        if ($client === null) {
            throw new UserNotFoundException('Payment client was not found');
        }

        $this->balanceService->reverseClientCredit($client, $payment->getAmount());
    }

    private function reverseTrainerPayout(Payment $payment): void
    {
        $trainerId = $payment->getTrainer()?->getId();
        if ($trainerId === null) {
            return;
        }

        $trainer = $this->findTrainerForUpdate($trainerId);
        if ($trainer === null) {
            throw new TrainerNotFoundException('Trainer for refunded payment was not found');
        }

        $this->balanceService->chargeTrainer($trainer, $payment->getAmount());
    }

    private function createRefundPaymentRecord(Payment $payment): void
    {
        if ($this->paymentRepository->findRefundForOriginalPayment($payment) !== null) {
            return;
        }

        $clientId = $payment->getClient()?->getId();
        if ($clientId === null) {
            throw new UserNotFoundException('Payment client was not found');
        }

        $client = $this->findClientForUpdate($clientId);
        if ($client === null) {
            throw new UserNotFoundException('Payment client was not found');
        }

        $trainer = null;
        $trainerId = $payment->getTrainer()?->getId();
        if ($trainerId !== null) {
            $trainer = $this->findTrainerForUpdate($trainerId);
            if ($trainer === null) {
                throw new TrainerNotFoundException('Trainer for refunded payment was not found');
            }
        }

        $refundPayment = $this->paymentService->createPayment(
            $client,
            $payment->getAmount(),
            $payment->getCategory(),
            PaymentMethodEnum::CARD,
            $trainer,
        );

        $refundPayment->setOriginalPayment($payment);
        $refundPayment->setIsRefund(true);
        $refundPayment->setCurrency($payment->getCurrency());
        $refundPayment->setStripePaymentIntentId(null);
        $refundPayment->setExpiresAt(null);

        $this->paymentLifecycleService->transitionTo($refundPayment, PaymentStatusEnum::SUCCEEDED);
    }

    private function findPayment(string $intentId): Payment
    {
        $payment = $this->paymentRepository->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new PaymentNotFoundException('Payment for Stripe intent was not found');
        }

        return $payment;
    }

    private function requirePaymentId(Payment $payment): int
    {
        $paymentId = $payment->getId();
        if ($paymentId === null) {
            throw new PaymentNotFoundException('Payment id is missing');
        }

        return $paymentId;
    }

    private function findClientForUpdate(int $clientId): ?Client
    {
        $filters = $this->entityManager->getFilters();
        $softDeleteEnabled = $filters->isEnabled('softdeleteable');

        if ($softDeleteEnabled) {
            $filters->disable('softdeleteable');
        }

        try {
            $client = $this->entityManager->find(
                Client::class,
                $clientId,
                LockMode::PESSIMISTIC_WRITE,
            );

            return $client instanceof Client ? $client : null;
        } finally {
            if ($softDeleteEnabled) {
                $filters->enable('softdeleteable');
            }
        }
    }

    private function findTrainerForUpdate(int $trainerId): ?Trainer
    {
        $filters = $this->entityManager->getFilters();
        $softDeleteEnabled = $filters->isEnabled('softdeleteable');

        if ($softDeleteEnabled) {
            $filters->disable('softdeleteable');
        }

        try {
            $trainer = $this->entityManager->find(
                Trainer::class,
                $trainerId,
                LockMode::PESSIMISTIC_WRITE,
            );

            return $trainer instanceof Trainer ? $trainer : null;
        } finally {
            if ($softDeleteEnabled) {
                $filters->enable('softdeleteable');
            }
        }
    }
}
