<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Exception\InvalidPaymentStatusException;
use App\Payment\Exception\PaymentNotFoundException;
use App\Payment\Message\CancelStripeIntentMessage;
use App\Payment\Message\RefundPaymentMessage;
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Entity\Trainer;
use App\Trainer\Exception\TrainerNotFoundException;
use App\User\Exception\UserNotFoundException;
use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

final readonly class PaymentSettlementService
{
    public function __construct(
        private PaymentService $paymentService,
        private StripeService $stripeService,
        private PaymentLifecycleService $paymentLifecycleService,
        private PaymentRepository $paymentRepo,
        private BalanceService $balanceService,
        private LoggerInterface $paymentLogger,
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $em,
    )
    {}

    public function determinePaymentMethod(int $clientBalance, int $price): PaymentMethodEnum
    {
        return ($clientBalance >= $price)
            ? PaymentMethodEnum::BALANCE
            : PaymentMethodEnum::CARD;
    }

    /**
     * @throws InvalidPaymentStatusException
     */
    public function createBookingPayment(
        Client $client,
        int $price,
        Booking $booking,
        Trainer $trainer,
    ): Payment
    {
        $method = $this->determinePaymentMethod($client->getBalance(), $price);

        $payment = $this->paymentService->createPayment(
            $client,
            $price,
            PaymentCategoryEnum::TRAINER,
            $method,
            $trainer,
        );

        $booking->setPayment($payment);

        if ($method === PaymentMethodEnum::BALANCE) {
            $this->settleBookingBalancePayment($payment, $booking);
        }

        return $payment;
    }

    /**
     * @throws InvalidPaymentStatusException
     */
    public function settleBookingBalancePayment(Payment $payment, Booking $booking): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            throw new InvalidPaymentStatusException('Cannot settle not pending payment');
        }

        $client = $payment->getClient();
        $trainer = $payment->getTrainer();
        $amount = $payment->getAmount();

        if ($client === null || $trainer === null) {
            throw new InvalidPaymentStatusException('Payment is not fully initialized');
        }

        $this->balanceService->chargeClient($client, $amount);
        $this->balanceService->depositTrainer($trainer, $amount);

        $booking->confirm();

        $this->paymentLifecycleService->transitionTo($payment, PaymentStatusEnum::SUCCEEDED);
    }

    public function createTopUpPayment(
        Client $client,
        int $amount,
    ): Payment
    {
        return $this->paymentService->createPayment(
            $client,
            $amount,
            PaymentCategoryEnum::BALANCE_TOP_UP,
            PaymentMethodEnum::CARD,
        );
    }

    /**
     * @throws InvalidPaymentStatusException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function settleTopUpPayment(Payment $payment): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            throw new InvalidPaymentStatusException('Cannot settle not pending payment');
        }

        $clientId = $payment->getClient()?->getId();
        if ($clientId === null) {
            throw new InvalidPaymentStatusException('Payment is not fully initialized');
        }

        $lockedClient = $this->em->find(
            Client::class,
            $clientId,
            LockMode::PESSIMISTIC_WRITE,
        );

        if ($lockedClient === null) {
            throw new UserNotFoundException('Payment client was not found');
        }

        $amount = $payment->getAmount();

        $this->balanceService->depositClient($lockedClient, $amount);
        $this->paymentLifecycleService->transitionTo($payment, PaymentStatusEnum::SUCCEEDED);
    }

    /**
     * @throws InvalidPaymentStatusException
     */
    public function createMembershipPayment(
        Client $client,
        int $price,
        Membership $membership,
    ): Payment
    {
        $method = $this->determinePaymentMethod($client->getBalance(), $price);

        $payment = $this->paymentService->createPayment(
            $client,
            $price,
            PaymentCategoryEnum::MEMBERSHIP,
            $method,
        );

        $membership->setPayment($payment);

        if ($method === PaymentMethodEnum::BALANCE) {
            $this->settleMembershipBalancePayment($payment, $membership);
        }

        return $payment;
    }

    /**
     * @throws InvalidPaymentStatusException
     */
    public function settleMembershipBalancePayment(Payment $payment, Membership $membership): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            throw new InvalidPaymentStatusException('Cannot settle not pending payment');
        }

        $client = $payment->getClient();
        $amount = $payment->getAmount();

        if ($client === null) {
            throw new InvalidPaymentStatusException('Payment is not fully initialized');
        }

        $this->balanceService->chargeClient($client, $amount);

        $membership->activate();

        $this->paymentLifecycleService->transitionTo($payment, PaymentStatusEnum::SUCCEEDED);
    }

    /**
     * @throws ApiErrorException
     * @throws Throwable
     */
    public function createStripeIntent(Payment $payment): string
    {
        $paymentId = $this->requirePaymentId($payment);

        return $this->withLockedPayment($paymentId, function (Payment $lockedPayment): string {
            if ($lockedPayment->getStatus() !== PaymentStatusEnum::PENDING) {
                throw new InvalidPaymentStatusException('Payment already processed');
            }

            $expiresAt = $lockedPayment->getExpiresAt();
            if ($expiresAt !== null && $expiresAt < new DateTimeImmutable()) {
                throw new InvalidPaymentStatusException('Payment expired');
            }

            return $this->stripeService->createPaymentIntent($lockedPayment);
        });
    }

    public function handleStripeSuccess(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);

        if ($payment === null) {
            throw new PaymentNotFoundException('Payment for Stripe intent was not found');
        }

        $paymentId = $this->requirePaymentId($payment);

        $this->withLockedPayment($paymentId, function (Payment $lockedPayment) use ($intentId): void {
            if ($lockedPayment->getStatus() === PaymentStatusEnum::SUCCEEDED) {
                return;
            }

            if ($lockedPayment->getStatus() !== PaymentStatusEnum::PENDING) {
                if (in_array($lockedPayment->getStatus(), [PaymentStatusEnum::CANCELED, PaymentStatusEnum::FAILED], true)) {
                    $this->paymentLogger->critical('payment.stripe_success.reconciliation_required', [
                        'intent_id' => $intentId,
                        'current_status' => $lockedPayment->getStatus()->value,
                        'action' => 'initiating_stripe_refund'
                    ]);

                    $this->dispatchPaymentMessage(
                        new RefundPaymentMessage(
                            $this->requirePaymentId($lockedPayment),
                            $intentId,
                        )
                    );

                    return;
                }

                $this->paymentLogger->warning('payment.stripe_success.ignored', [
                    'intent_id' => $intentId,
                    'current_status' => $lockedPayment->getStatus()->value,
                ]);

                return;
            }

            $booking = $lockedPayment->getBooking();

            if ($booking !== null) {
                $trainer = $lockedPayment->getTrainer();
                if ($trainer === null) {
                    throw new TrainerNotFoundException('Trainer for booking payment was not found');
                }

                $lockedTrainer = $this->em->find(
                    Trainer::class,
                    $trainer->getId(),
                    LockMode::PESSIMISTIC_WRITE
                );

                if ($lockedTrainer === null) {
                    throw new TrainerNotFoundException('Trainer for booking payment was not found');
                }

                $this->balanceService->depositTrainer($lockedTrainer, $lockedPayment->getAmount());

                $booking->confirm();
            }

            $membership = $lockedPayment->getMembership();
            if ($membership !== null) {
                $this->em->refresh($membership);

                if ($membership->getStatus() !== MembershipStatusEnum::PENDING) {
                    $refundMessage = $this->refundSucceededStripePaymentForProcessedMembership(
                        $lockedPayment,
                        $membership,
                        $intentId
                    );

                    $this->dispatchPaymentMessage($refundMessage);

                    return;
                }

                $membership->activate();
            }

            if ($lockedPayment->getCategory() === PaymentCategoryEnum::BALANCE_TOP_UP) {
                $this->settleTopUpPayment($lockedPayment);

                return;
            }

            $this->paymentLifecycleService->transitionTo($lockedPayment, PaymentStatusEnum::SUCCEEDED);
        });
    }

    /**
     * @throws Throwable
     */
    public function handleStripeRefundSucceeded(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new PaymentNotFoundException('Payment for Stripe intent was not found');
        }

        $this->withLockedPayment($this->requirePaymentId($payment), function (Payment $lockedPayment) use ($intentId) {
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

            if (in_array($lockedPayment->getStatus(), [PaymentStatusEnum::REFUND_PENDING, PaymentStatusEnum::REFUND_FAILED, PaymentStatusEnum::SUCCEEDED], true)) {
                $this->reverseTrainerPayoutForRefund($lockedPayment);
            }

            $membership = $lockedPayment->getMembership();
            if ($membership !== null && $lockedPayment->getStatus() === PaymentStatusEnum::SUCCEEDED) {
                $this->paymentLogger->critical('payment.stripe_refund.membership_reconciliation_required', [
                    'intent_id' => $intentId,
                    'payment_id' => $lockedPayment->getId(),
                    'membership_id' => $membership->getId(),
                    'membership_status' => $membership->getStatus()->value,
                ]);
            }

            $this->createStripeRefundPaymentRecord($lockedPayment);
            $this->paymentLifecycleService->transitionTo($lockedPayment, PaymentStatusEnum::REFUNDED);
        });
    }

    /**
     * @throws Throwable
     */
    public function handleStripeDisputeCreated(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new PaymentNotFoundException('Payment for Stripe intent was not found');
        }

        $this->withLockedPayment($this->requirePaymentId($payment), function (Payment $lockedPayment) use ($intentId) {
            if ($lockedPayment->getStatus() === PaymentStatusEnum::REFUNDED) {
                return;
            }

            $this->paymentLogger->critical('payment.stripe_dispute.created', [
                'intent_id' => $intentId,
                'payment_id' => $lockedPayment->getId(),
                'current_status' => $lockedPayment->getStatus()->value,
                'action' => 'recording_dispute_reversal',
            ]);

            if (in_array($lockedPayment->getStatus(), [PaymentStatusEnum::REFUND_PENDING, PaymentStatusEnum::REFUND_FAILED, PaymentStatusEnum::SUCCEEDED], true)) {
                $this->reverseTrainerPayoutForRefund($lockedPayment);
            }

            $this->createStripeRefundPaymentRecord($lockedPayment);
            $this->paymentLifecycleService->transitionTo($lockedPayment, PaymentStatusEnum::REFUNDED);
        });
    }

    /**
     * @throws Throwable
     */
    public function handleStripeRefundFailed(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new PaymentNotFoundException('Payment for Stripe intent was not found');
        }

        $this->withLockedPayment($this->requirePaymentId($payment), function (Payment $lockedPayment) use ($intentId) {
            if ($lockedPayment->getStatus() === PaymentStatusEnum::REFUND_PENDING) {
                $this->paymentLifecycleService->transitionTo($lockedPayment, PaymentStatusEnum::REFUND_FAILED);
            }

            $this->paymentLogger->critical('payment.stripe_refund.failed', [
                'intent_id' => $intentId,
                'payment_id' => $lockedPayment->getId(),
                'current_status' => $lockedPayment->getStatus()->value,
                'action' => 'manual_reconciliation_required',
            ]);
        });
    }

    /**
     * @throws Throwable
     */
    public function refund(Payment $payment): ?RefundPaymentMessage
    {
        if ($payment->getIsRefund()) {
            $this->paymentLogger->warning(
                'payment.refund.rejected',
                $this->paymentEventContext($payment, 'refund', 'rejected', [
                    'error' => 'Payment already refunded',
                ])
            );

            return null;
        }

        $paymentId = $this->requirePaymentId($payment);

        return $this->withLockedPayment($paymentId, function (Payment $lockedPayment) {
            if (in_array($lockedPayment->getStatus(), [PaymentStatusEnum::REFUNDED, PaymentStatusEnum::REFUND_PENDING], true)) {
                $this->paymentLogger->warning(
                    'payment.refund.rejected.already_refunded',
                    $this->paymentEventContext($lockedPayment, 'refund', 'rejected', [
                        'error' => 'Payment already refunded or pending refund',
                    ])
                );

                return null;
            }

            if ($lockedPayment->getStatus() !== PaymentStatusEnum::SUCCEEDED) {
                throw new InvalidPaymentStatusException('Only succeeded payments can be refunded');
            }

            if ($lockedPayment->getMethod() === PaymentMethodEnum::CARD) {
                return $this->requestStripeCardRefund($lockedPayment);
            }

            $this->refundBalancePaymentToInternalBalance($lockedPayment);

            return null;
        });
    }

    private function requestStripeCardRefund(Payment $lockedPayment): RefundPaymentMessage
    {
        $intentId = $lockedPayment->getStripePaymentIntentId();
        if ($intentId === null) {
            throw new InvalidPaymentStatusException('Card payment is missing Stripe PaymentIntent ID');
        }

        $this->paymentLifecycleService->transitionTo($lockedPayment, PaymentStatusEnum::REFUND_PENDING);

        $this->paymentLogger->info(
            'payment.card_refund.requested',
            $this->paymentEventContext($lockedPayment, 'refund', 'requested', [
                'intent_id' => $intentId,
            ])
        );

        return new RefundPaymentMessage(
            $this->requirePaymentId($lockedPayment),
            $intentId
        );
    }

    /**
     * @throws Throwable
     */
    private function refundBalancePaymentToInternalBalance(Payment $lockedPayment): void
    {
        $this->paymentLifecycleService->transitionTo($lockedPayment, PaymentStatusEnum::REFUNDED);

        $clientId = $lockedPayment->getClient()?->getId();
        if ($clientId === null) {
            throw new UserNotFoundException('Payment client was not found');
        }

        $lockedClient = $this->em->find(
            Client::class,
            $clientId,
            LockMode::PESSIMISTIC_WRITE
        );

        if ($lockedClient === null) {
            throw new UserNotFoundException('Payment client was not found');
        }

        $this->balanceService->depositClient($lockedClient, $lockedPayment->getAmount());
        $this->reverseTrainerPayoutForRefund($lockedPayment);

        $refundPayment = $this->paymentService->createPayment(
            $lockedClient,
            $lockedPayment->getAmount(),
            $lockedPayment->getCategory(),
            PaymentMethodEnum::BALANCE,
            $lockedPayment->getTrainer(),
        );

        $refundPayment->setOriginalPayment($lockedPayment);
        $refundPayment->setIsRefund(true);

        $this->paymentLifecycleService->transitionTo($refundPayment, PaymentStatusEnum::SUCCEEDED);
    }

    private function createStripeRefundPaymentRecord(Payment $lockedPayment): void
    {
        if ($this->paymentRepo->findRefundForOriginalPayment($lockedPayment) !== null) {
            return;
        }

        $client = $lockedPayment->getClient();
        if ($client === null) {
            throw new UserNotFoundException('Payment client was not found');
        }

        $refundPayment = $this->paymentService->createPayment(
            $client,
            $lockedPayment->getAmount(),
            $lockedPayment->getCategory(),
            PaymentMethodEnum::CARD,
            $lockedPayment->getTrainer(),
        );

        $refundPayment->setOriginalPayment($lockedPayment);
        $refundPayment->setIsRefund(true);
        $refundPayment->setCurrency($lockedPayment->getCurrency());
        $refundPayment->setStripePaymentIntentId(null);
        $refundPayment->setExpiresAt(null);
        $refundPayment->setPaidAt(new DateTimeImmutable());

        $this->paymentLifecycleService->transitionTo($refundPayment, PaymentStatusEnum::SUCCEEDED);
    }

    /**
     * @throws Throwable
     */
    public function failPayment(Payment $payment, bool $cancelRemoteIntent = true): ?CancelStripeIntentMessage
    {
        try {
            if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
                return null;
            }

            $this->cancelRelatedBookingForPaymentFailure($payment);

            $payment->getMembership()?->cancel(MembershipStatusEnum::CANCELED_PAYMENT_FAILED);

            if (
                $cancelRemoteIntent
                && $payment->getMethod() === PaymentMethodEnum::CARD
                && $payment->getStripePaymentIntentId() !== null
            ) {
                $cancelStripeIntentMessage = new CancelStripeIntentMessage(
                    $this->requirePaymentId($payment),
                    $payment->getStripePaymentIntentId()
                );
            } else {
                $cancelStripeIntentMessage = null;
            }

            $this->paymentLifecycleService->transitionTo($payment, PaymentStatusEnum::FAILED);

            return $cancelStripeIntentMessage;
        } catch (Throwable $e) {
            $this->paymentLogger->error(
                'payment.fail.failed',
                $this->paymentEventContext($payment, 'fail', 'failed', [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                ])
            );

            throw $e;
        }
    }


    /**
     * @throws Throwable
     */
    public function cancelPaymentByStripeIntentId(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new PaymentNotFoundException('Payment for Stripe intent was not found');
        }

        $this->withLockedPayment($this->requirePaymentId($payment), function (Payment $lockedPayment) {
            $this->cancelPayment($lockedPayment, false);
        });
    }

    /**
     * @throws Throwable
     */
    public function cancelPayment(Payment $payment, bool $cancelRemoteIntent = true): ?CancelStripeIntentMessage
    {
        try {
            if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
                return null;
            }

            $this->cancelRelatedBookingForPaymentFailure($payment);

            $payment->getMembership()?->cancel(MembershipStatusEnum::CANCELED_PAYMENT_FAILED);

            if (
                $cancelRemoteIntent
                && $payment->getMethod() === PaymentMethodEnum::CARD
                && $payment->getStripePaymentIntentId() !== null
            ) {
                $cancelStripeIntentMessage = new CancelStripeIntentMessage(
                    $this->requirePaymentId($payment),
                    $payment->getStripePaymentIntentId()
                );
            } else {
                $cancelStripeIntentMessage = null;
            }

            $this->paymentLifecycleService->transitionTo($payment, PaymentStatusEnum::CANCELED);

            return $cancelStripeIntentMessage;
        } catch (Throwable $e) {
            $this->paymentLogger->error(
                'payment.cancel.failed',
                $this->paymentEventContext($payment, 'cancel', 'failed', [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                ])
            );

            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function cancelExpiredPayments(): void
    {
        $payments = $this->paymentRepo->findExpiredPending();

        $now = new DateTimeImmutable();

        foreach ($payments as $payment) {
            $expiresAt = $payment->getExpiresAt();
            if ($expiresAt !== null && $expiresAt < $now) {
                $this->withLockedPayment(
                    $this->requirePaymentId($payment),
                    function (Payment $lockedPayment): void {
                        $message = $this->cancelPayment($lockedPayment);
                        $this->dispatchPaymentMessage($message);
                    }
                );
            }
        }
    }

    public function dispatchPaymentMessage(RefundPaymentMessage|CancelStripeIntentMessage|null $message): void
    {
        if ($message === null) {
            return;
        }

        $this->messageBus->dispatch($message);
    }

    private function reverseTrainerPayoutForRefund(Payment $payment): void
    {
        $trainer = $payment->getTrainer();
        if ($trainer === null) {
            return;
        }

        $trainerId = $trainer->getId();
        if ($trainerId === null) {
            throw new TrainerNotFoundException('Trainer for refunded payment was not found');
        }

        $lockedTrainer = $this->em->find(
            Trainer::class,
            $trainerId,
            LockMode::PESSIMISTIC_WRITE
        );

        if ($lockedTrainer === null) {
            throw new TrainerNotFoundException('Trainer for refunded payment was not found');
        }

        $this->balanceService->chargeTrainer($lockedTrainer, $payment->getAmount());
    }

    private function refundSucceededStripePaymentForProcessedMembership(
        Payment $payment,
        Membership $membership,
        string $intentId,
    ): RefundPaymentMessage {
        $paymentId = $this->requirePaymentId($payment);

        $this->paymentLogger->critical('payment.stripe_success.membership_reconciliation_required', [
            'intent_id' => $intentId,
            'payment_id' => $paymentId,
            'membership_id' => $membership->getId(),
            'membership_status' => $membership->getStatus()->value,
            'action' => 'initiating_stripe_refund',
        ]);

        $this->paymentLifecycleService->transitionTo($payment, PaymentStatusEnum::CANCELED);

        return new RefundPaymentMessage($paymentId, $intentId);
    }

    private function requirePaymentId(Payment $payment): int
    {
        $paymentId = $payment->getId();
        if ($paymentId === null) {
            throw new PaymentNotFoundException('Payment id is missing');
        }

        return $paymentId;
    }

    private function cancelRelatedBookingForPaymentFailure(Payment $payment): void
    {
        $booking = $payment->getBooking();

        if ($booking === null) {
            return;
        }

        $booking->setStatus(BookingStatusEnum::CANCELED_PAYMENT_FAILED);
        $booking->getTraining()?->setIsBusy(false);
    }

    /**
     * @template T
     * @param callable(Payment): T $callback
     * @return T
     */
    public function withLockedPayment(int $paymentId, callable $callback): mixed
    {
        return $this->em->wrapInTransaction(function () use ($paymentId, $callback) {
            $row = $this->em->getConnection()
                ->executeQuery(
                    'SELECT id FROM payment WHERE id = :id FOR UPDATE',
                    ['id' => $paymentId]
                )
                ->fetchAssociative();

            if ($row === false) {
                throw new PaymentNotFoundException('Payment not found');
            }

            $lockedPayment = $this->em->find(Payment::class, $paymentId);
            if ($lockedPayment === null) {
                throw new PaymentNotFoundException('Payment not found');
            }

            $this->em->refresh($lockedPayment);

            return $callback($lockedPayment);
        });
    }

    /**
     * @param array<string, scalar|null> $extra
     * @return array<string, scalar|null>
     */
    private function paymentEventContext(Payment $payment, string $operation, string $outcome, array $extra = []): array
    {
        return $extra + [
                'domain' => 'payment',
                'operation' => $operation,
                'outcome' => $outcome,
                'payment_id' => $payment->getId(),
                'client_id' => $payment->getClient()?->getId(),
                'trainer_id' => $payment->getTrainer()?->getId(),
                'booking_id' => $payment->getBooking()?->getId(),
                'membership_id' => $payment->getMembership()?->getId(),
                'category' => $payment->getCategory()->value,
                'method' => $payment->getMethod()->value,
                'status' => $payment->getStatus()->value,
            ];
    }
}
