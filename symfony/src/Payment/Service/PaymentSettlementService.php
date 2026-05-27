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
    public function settleTopUpPayment(Payment $payment): void
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

        $amount = $payment->getAmount();

        $this->balanceService->depositClient($lockedClient, $amount);
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
        return $this->stripeService->createPaymentIntent($payment);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function handleStripeSuccess(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new PaymentNotFoundException('Payment for Stripe intent was not found');
        }

        if ($payment->getStatus() === PaymentStatusEnum::SUCCEEDED) {
            return;
        }

        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            $this->paymentLogger->warning('payment.stripe_success.ignored', [
                'intent_id' => $intentId,
                'current_status' => $payment->getStatus()->value,
            ]);
            return;
        }

        $booking = $payment->getBooking();

        $this->em->wrapInTransaction(function () use ($payment, $booking) {
            if ($booking !== null) {
                $trainer = $payment->getTrainer();
                if ($trainer === null) {
                    throw new TrainerNotFoundException('Trainer for booking payment was not found');
                }

                $lockedTrainer = $this->em->find(
                    Trainer::class,
                    $trainer->getId(),
                    LockMode::PESSIMISTIC_WRITE
                );

                $this->balanceService->depositTrainer($lockedTrainer, $payment->getAmount());

                $booking->confirm();
            }

            $membership = $payment->getMembership();
            $membership?->activate();

            if ($payment->getCategory() === PaymentCategoryEnum::BALANCE_TOP_UP) {
                $this->settleTopUpPayment($payment);
            }

            $this->paymentLifecycleService->transitionTo($payment, PaymentStatusEnum::SUCCEEDED);
        });
    }

    /**
     * @throws Throwable
     */
    public function refund(Payment $payment): void
    {
        if ($payment->getIsRefund()) {
            $this->paymentLogger->warning(
                'payment.refund.rejected',
                $this->paymentEventContext($payment, 'refund', 'rejected', [
                    'error' => 'Payment already refunded',
                ])
            );

            return;
        }

        $paymentId = $payment->getId();

        $this->em->wrapInTransaction(function () use ($paymentId) {
            $this->em->getConnection()->executeStatement(
                'SELECT id FROM payment WHERE id = :id FOR UPDATE',
                ['id' => $paymentId]
            );

            $lockedPayment = $this->em->find(Payment::class, $paymentId);

            if ($lockedPayment === null) {
                throw new PaymentNotFoundException('Payment not found during refund');
            }

            if ($lockedPayment->getStatus() === PaymentStatusEnum::REFUNDED) {
                $this->paymentLogger->warning(
                    'payment.refund.rejected.already_refunded',
                    $this->paymentEventContext($lockedPayment, 'refund', 'rejected', [
                        'error' => 'Payment already refunded',
                    ])
                );
                return;
            }

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

            $this->balanceService->depositClient($lockedClient, $lockedPayment->getAmount());

            $trainer = $lockedPayment->getTrainer();
            if ($trainer !== null) {
                $lockedTrainer = $this->em->find(
                    Trainer::class,
                    $trainer->getId(),
                    LockMode::PESSIMISTIC_WRITE
                );

                $this->balanceService->chargeTrainer($lockedTrainer, $lockedPayment->getAmount());
            }

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
        });
    }

    /**
     * @throws Throwable
     */
    public function failPayment(Payment $payment): void
    {
        try {
            if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
                return;
            }

            $this->paymentLifecycleService->transitionTo($payment, PaymentStatusEnum::FAILED);
            $payment->getBooking()?->setStatus(BookingStatusEnum::CANCELED_PAYMENT_FAILED);
            $payment->getMembership()?->cancel(MembershipStatusEnum::CANCELED_PAYMENT_FAILED);

            $this->em->flush();
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
    public function failPaymentByStripeIntentId(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new PaymentNotFoundException('Payment for Stripe intent was not found');
        }

        $this->failPayment($payment);
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

        $this->cancelPayment($payment);
    }

    /**
     * @throws Throwable
     */
    public function cancelPayment(Payment $payment): void
    {
        try {
            if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
                return;
            }

            $payment->getBooking()?->setStatus(BookingStatusEnum::CANCELED_PAYMENT_FAILED);
            $payment->getMembership()?->cancel(MembershipStatusEnum::CANCELED_PAYMENT_FAILED);
            $this->paymentLifecycleService->transitionTo($payment, PaymentStatusEnum::CANCELED);

            $this->em->flush();
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
                $this->cancelPayment($payment);
            }
        }

        $this->em->flush();
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
