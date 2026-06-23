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
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class StripeRefundSettlementService
{
    /** @var list<PaymentStatusEnum> */
    private const array REFUNDABLE_STATUSES = [
        PaymentStatusEnum::REFUND_PENDING,
        PaymentStatusEnum::REFUND_FAILED,
        PaymentStatusEnum::SUCCEEDED,
        PaymentStatusEnum::CANCELED,
        PaymentStatusEnum::FAILED,
    ];

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
                    PaymentStatusEnum::CANCELED,
                    PaymentStatusEnum::FAILED,
                ], true)) {
                    $this->paymentLifecycleService->transitionTo(
                        $lockedPayment,
                        PaymentStatusEnum::REFUND_PENDING,
                    );
                }
            },
        );
    }

    public function handleChargeRefunded(string $intentId, int $cumulativeRefundAmount): void
    {
        $payment = $this->findPayment($intentId);

        $this->paymentSettlementService->withLockedPayment(
            $this->requirePaymentId($payment),
            function (Payment $lockedPayment) use ($intentId, $cumulativeRefundAmount): void {
                if ($cumulativeRefundAmount <= 0 || $cumulativeRefundAmount > $lockedPayment->getAmount()) {
                    $this->paymentLogger->critical('payment.stripe_refund.invalid_amount', [
                        'intent_id' => $intentId,
                        'payment_id' => $lockedPayment->getId(),
                        'payment_amount' => $lockedPayment->getAmount(),
                        'refund_amount' => $cumulativeRefundAmount,
                        'action' => 'manual_reconciliation_required',
                    ]);

                    return;
                }

                if ($cumulativeRefundAmount === $lockedPayment->getAmount()) {
                    $this->settleFullRefund($lockedPayment, $intentId);

                    return;
                }

                $this->settlePartialRefund(
                    $lockedPayment,
                    $intentId,
                    $cumulativeRefundAmount,
                );
            },
        );
    }

    public function handleSucceeded(string $intentId, ?int $refundAmount = null): void
    {
        $payment = $this->findPayment($intentId);

        $this->paymentSettlementService->withLockedPayment(
            $this->requirePaymentId($payment),
            function (Payment $lockedPayment) use ($intentId, $refundAmount): void {
                if ($refundAmount !== null && $refundAmount !== $lockedPayment->getAmount()) {
                    $this->paymentLogger->warning('payment.stripe_refund.awaiting_charge_event', [
                        'intent_id' => $intentId,
                        'payment_id' => $lockedPayment->getId(),
                        'payment_amount' => $lockedPayment->getAmount(),
                        'refund_amount' => $refundAmount,
                    ]);

                    return;
                }

                $this->settleFullRefund($lockedPayment, $intentId);
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

                if (in_array($lockedPayment->getStatus(), [
                    PaymentStatusEnum::SUCCEEDED,
                    PaymentStatusEnum::CANCELED,
                    PaymentStatusEnum::FAILED,
                ], true)) {
                    $this->paymentLifecycleService->transitionTo(
                        $lockedPayment,
                        PaymentStatusEnum::REFUND_PENDING,
                    );
                }

                if ($lockedPayment->getStatus() !== PaymentStatusEnum::REFUND_PENDING) {
                    $this->paymentLogger->warning('payment.stripe_refund.failure_ignored', [
                        'intent_id' => $intentId,
                        'payment_id' => $lockedPayment->getId(),
                        'current_status' => $lockedPayment->getStatus()->value,
                    ]);

                    return;
                }

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
            },
        );
    }

    public function handleActionRequired(string $intentId): void
    {
        $this->markPending($intentId);
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

    private function settleFullRefund(Payment $payment, string $intentId): void
    {
        if ($payment->getStatus() === PaymentStatusEnum::REFUNDED) {
            return;
        }

        if (!in_array($payment->getStatus(), self::REFUNDABLE_STATUSES, true)) {
            $this->paymentLogger->warning('payment.stripe_refund.ignored', [
                'intent_id' => $intentId,
                'payment_id' => $payment->getId(),
                'current_status' => $payment->getStatus()->value,
            ]);

            return;
        }

        $refundPayment = $this->paymentRepository->findRefundForOriginalPayment($payment);
        $alreadyRefundedAmount = $refundPayment?->getAmount() ?? 0;

        if ($alreadyRefundedAmount > $payment->getAmount()) {
            $this->paymentLogger->critical('payment.stripe_refund.record_exceeds_payment', [
                'intent_id' => $intentId,
                'payment_id' => $payment->getId(),
                'payment_amount' => $payment->getAmount(),
                'recorded_refund_amount' => $alreadyRefundedAmount,
                'action' => 'manual_reconciliation_required',
            ]);

            return;
        }

        if ($this->wasSettledLocally($payment)) {
            $this->reverseSettledPaymentEffects($payment, $alreadyRefundedAmount);
        }

        $this->upsertRefundPaymentRecord($payment, $payment->getAmount());
        $this->paymentLifecycleService->transitionTo($payment, PaymentStatusEnum::REFUNDED);
    }

    private function settlePartialRefund(
        Payment $payment,
        string $intentId,
        int $cumulativeRefundAmount,
    ): void {
        if ($payment->getStatus() === PaymentStatusEnum::REFUNDED) {
            return;
        }

        if (!in_array($payment->getStatus(), self::REFUNDABLE_STATUSES, true)) {
            $this->paymentLogger->warning('payment.stripe_refund.partial_ignored', [
                'intent_id' => $intentId,
                'payment_id' => $payment->getId(),
                'current_status' => $payment->getStatus()->value,
                'refund_amount' => $cumulativeRefundAmount,
            ]);

            return;
        }

        $refundPayment = $this->paymentRepository->findRefundForOriginalPayment($payment);
        $alreadyRefundedAmount = $refundPayment?->getAmount() ?? 0;

        if ($cumulativeRefundAmount <= $alreadyRefundedAmount) {
            if ($cumulativeRefundAmount < $alreadyRefundedAmount) {
                $this->paymentLogger->warning('payment.stripe_refund.out_of_order_amount', [
                    'intent_id' => $intentId,
                    'payment_id' => $payment->getId(),
                    'recorded_refund_amount' => $alreadyRefundedAmount,
                    'received_refund_amount' => $cumulativeRefundAmount,
                ]);
            }

            return;
        }

        $refundDelta = $cumulativeRefundAmount - $alreadyRefundedAmount;
        $clientCreditReversed = false;

        if (
            $payment->getCategory() === PaymentCategoryEnum::BALANCE_TOP_UP
            && $this->wasSettledLocally($payment)
        ) {
            $this->reverseTopUpCredit($payment, $refundDelta);
            $clientCreditReversed = true;
        }

        $this->upsertRefundPaymentRecord($payment, $cumulativeRefundAmount);

        $this->paymentLogger->critical('payment.stripe_refund.partial_recorded', [
            'intent_id' => $intentId,
            'payment_id' => $payment->getId(),
            'payment_amount' => $payment->getAmount(),
            'refund_amount' => $cumulativeRefundAmount,
            'refund_delta' => $refundDelta,
            'action' => $clientCreditReversed
                ? 'client_credit_reversed'
                : 'manual_business_effect_reconciliation_required',
        ]);
    }

    private function reverseSettledPaymentEffects(Payment $payment, int $alreadyRefundedAmount): void
    {
        if ($payment->getCategory() === PaymentCategoryEnum::BALANCE_TOP_UP) {
            $remainingAmount = $payment->getAmount() - $alreadyRefundedAmount;
            if ($remainingAmount > 0) {
                $this->reverseTopUpCredit($payment, $remainingAmount);
            }
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

    private function reverseTopUpCredit(Payment $payment, int $amount): void
    {
        $clientId = $payment->getClient()?->getId();
        if ($clientId === null) {
            throw new UserNotFoundException('Payment client was not found');
        }

        $client = $this->findClientForUpdate($clientId);
        if ($client === null) {
            throw new UserNotFoundException('Payment client was not found');
        }

        $this->balanceService->reverseClientCredit($client, $amount);
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

    private function upsertRefundPaymentRecord(Payment $payment, int $refundAmount): void
    {
        $refundPayment = $this->paymentRepository->findRefundForOriginalPayment($payment);
        if ($refundPayment !== null) {
            $refundPayment->setAmount($refundAmount);

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
            $refundAmount,
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

    private function wasSettledLocally(Payment $payment): bool
    {
        return $payment->getPaidAt() !== null;
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
            $row = $this->entityManager->getConnection()
                ->executeQuery(
                    'SELECT id FROM "user" WHERE id = :id FOR UPDATE',
                    ['id' => $clientId],
                )
                ->fetchAssociative();

            if ($row === false) {
                return null;
            }

            $client = $this->entityManager->find(Client::class, $clientId);

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
            $row = $this->entityManager->getConnection()
                ->executeQuery(
                    'SELECT id FROM "user" WHERE id = :id FOR UPDATE',
                    ['id' => $trainerId],
                )
                ->fetchAssociative();

            if ($row === false) {
                return null;
            }

            $trainer = $this->entityManager->find(Trainer::class, $trainerId);

            return $trainer instanceof Trainer ? $trainer : null;
        } finally {
            if ($softDeleteEnabled) {
                $filters->enable('softdeleteable');
            }
        }
    }
}
