<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Exception\InvalidPaymentStatusException;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
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

    public function settleBookingBalancePayment(Payment $payment, Booking $booking): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            throw new InvalidPaymentStatusException('Cannot settle not pending payment');
        }

        $client = $payment->getClient();
        $trainer = $payment->getTrainer();
        $amount = $payment->getAmount();

        $client->setBalance($client->getBalance() - $amount);
        $trainer->setBalance($trainer->getBalance() + $amount);

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

    public function settleTopUpPayment(Payment $payment): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            throw new InvalidPaymentStatusException('Cannot settle not pending payment');
        }

        $client = $payment->getClient();
        $amount = $payment->getAmount();

        $client->setBalance($client->getBalance() + $amount);
    }

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

    public function settleMembershipBalancePayment(Payment $payment, Membership $membership): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            throw new InvalidPaymentStatusException('Cannot settle not pending payment');
        }

        $client = $payment->getClient();
        $amount = $payment->getAmount();

        $client->setBalance($client->getBalance() - $amount);

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

    public function handleStripeSuccess(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new NotFoundHttpException('Payment for Stripe intent was not found');
        }

        $booking = $payment->getBooking();

        $this->em->wrapInTransaction(function () use ($payment, $booking) {
            if ($booking) {
                $trainer = $payment->getTrainer();
                $trainer->setBalance(
                    $trainer->getBalance() + $payment->getAmount()
                );

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

        $this->paymentLifecycleService->transitionTo($payment, PaymentStatusEnum::REFUNDED);

        $client = $payment->getClient();
        $client->setBalance(
            $client->getBalance() + $payment->getAmount()
        );

        if ($trainer = $payment->getTrainer()) {
            $trainer->setBalance(
                $trainer->getBalance() - $payment->getAmount()
            );
        }

        $refundPayment = $this->paymentService->createPayment(
            $payment->getClient(),
            $payment->getAmount(),
            $payment->getCategory(),
            PaymentMethodEnum::BALANCE,
            $payment->getTrainer(),
        );
        $refundPayment->setIsRefund(true);

        $this->paymentLifecycleService->transitionTo($refundPayment, PaymentStatusEnum::SUCCEEDED);
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
            $payment->getBooking()?->cancel(BookingStatusEnum::CANCELED_PAYMENT_FAILED);
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
            throw new NotFoundHttpException('Payment for Stripe intent was not found');
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
            throw new NotFoundHttpException('Payment for Stripe intent was not found');
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

            $payment->getBooking()?->cancel(BookingStatusEnum::CANCELED_PAYMENT_FAILED);
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
            if ($payment->getExpiresAt() < $now) {
                $this->cancelPayment($payment);
            }
        }

        $this->em->flush();
    }


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
                'category' => $payment->getCategory()?->value,
                'method' => $payment->getMethod()->value,
                'status' => $payment->getStatus()->value,
            ];
    }
}
