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
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final readonly class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepo,
        private StripeService $stripeService,
        private EntityManagerInterface $em,
        private LoggerInterface $paymentLogger,
    ) {}

    public function createPayment(
        Client $client,
        int $amount,
        PaymentCategoryEnum $category,
        PaymentMethodEnum $method,
        ?Trainer $trainer = null
    ): Payment {
        $payment = new Payment($method);
        $payment->setClient($client);
        $payment->setAmount($amount);
        $payment->setIsRefund(false);
        $payment->setCategory($category);
        $payment->setStatus(PaymentStatusEnum::PENDING);
        $payment->setExpiresAt(new DateTimeImmutable('+15 minutes'));

        if ($trainer) {
            $payment->setTrainer($trainer);
        }

        $this->paymentRepo->create($payment);

        $this->paymentLogger->info(
            'payment.create.succeeded',
            $this->paymentEventContext($payment, 'create', 'succeeded', [
                'amount' => $amount,
            ])
        );

        return $payment;
    }

    /**
     * @throws Throwable
     */
    public function confirmPayment(Payment $payment, ?Membership $membership = null, ?Booking $booking = null): void
    {
        try {
            if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
                return;
            }

            $amount = $payment->getAmount();
            if ($amount === null) {
                throw new LogicException('Payment amount is not set');
            }

            match ($payment->getCategory()) {
                PaymentCategoryEnum::TRAINER => $this->confirmBookingPayment($payment, $amount, $booking),
                PaymentCategoryEnum::MEMBERSHIP => $this->confirmMembershipPayment($payment, $amount, $membership),
                PaymentCategoryEnum::BALANCE_TOP_UP => $this->confirmTopUpPayment($payment, $amount),
            };

            $payment->setStatus(PaymentStatusEnum::SUCCEEDED);
            $payment->setConfirmedAt(new DateTimeImmutable());
            $payment->setPaidAt($payment->getPaidAt() ?? new DateTimeImmutable());
            $payment->setExpiresAt(null);

            $this->em->flush();

            $this->paymentLogger->info(
                'payment.confirm.succeeded',
                $this->paymentEventContext($payment, 'confirm', 'succeeded', [
                    'amount' => $amount,
                ])
            );

        } catch (Throwable $e) {
            $this->paymentLogger->error(
                'payment.confirm.failed',
                $this->paymentEventContext($payment, 'confirm', 'failed', [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                ])
            );

            throw $e;
        }
    }

    private function confirmBookingPayment(Payment $payment, int $amount, ?Booking $booking = null): void
    {
        if ($payment->getMethod() === PaymentMethodEnum::BALANCE) {
            $payment->getClient()?->setBalance(
                $payment->getClient()->getBalance() - $amount
            );
        }

        if ($trainer = $payment->getTrainer()) {
            $trainer->setBalance($trainer->getBalance() + $amount);
        }

        $booking ??= $payment->getBooking();

        if (!$booking) {
            throw new LogicException('Booking required');
        }

        $booking->confirm();
    }

    private function confirmMembershipPayment(Payment $payment, int $amount, ?Membership $membership = null): void
    {
        if ($payment->getMethod() === PaymentMethodEnum::BALANCE) {
            $payment->getClient()?->setBalance(
                $payment->getClient()->getBalance() - $amount
            );
        }

        $membership ??= $payment->getMembership();

        if (!$membership) {
            throw new LogicException('Membership required');
        }

        $membership->activate();
    }

    private function confirmTopUpPayment(Payment $payment, int $amount): void
    {
        $payment->getClient()?->setBalance(
            $payment->getClient()->getBalance() + $amount
        );
    }

    /**
     * @throws Throwable
     */
    public function confirmPaymentByStripeIntentId(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new NotFoundHttpException('Payment for Stripe intent was not found');
        }

        $this->confirmPayment($payment);
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

            $payment->setStatus(PaymentStatusEnum::FAILED);
            $payment->getBooking()?->cancel(BookingStatusEnum::CANCELED_PAYMENT_FAILED);
            $payment->getMembership()?->cancel(MembershipStatusEnum::CANCELED_PAYMENT_FAILED);

            $this->em->flush();

            $this->paymentLogger->warning(
                'payment.fail.succeeded',
                $this->paymentEventContext($payment, 'fail', 'succeeded')
            );

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
    public function refundPayment(Payment $payment): void
    {
        try {
            if ($payment->getStatus() === PaymentStatusEnum::REFUNDED) {
                return;
            }

            if ($payment->getStatus() !== PaymentStatusEnum::SUCCEEDED) {
                throw new LogicException('Cannot refund unpaid payment');
            }

            $amount = $payment->getAmount();

            $payment->getClient()?->setBalance(
                $payment->getClient()->getBalance() + $amount
            );

            if ($trainer = $payment->getTrainer()) {
                $trainer->setBalance($trainer->getBalance() - $amount);
            }

            $payment->setIsRefund(true);
            $payment->setStatus(PaymentStatusEnum::REFUNDED);

            $this->em->flush();

            $this->paymentLogger->info(
                'payment.refund.succeeded',
                $this->paymentEventContext($payment, 'refund', 'succeeded', [
                    'amount' => $amount,
                ])
            );

        } catch (Throwable $e) {
            $this->paymentLogger->error(
                'payment.refund.failed',
                $this->paymentEventContext($payment, 'refund', 'failed', [
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
    public function refundPaymentByStripeIntentId(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new NotFoundHttpException('Payment for Stripe intent was not found');
        }

        $this->refundPayment($payment);
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

            $payment->setStatus(PaymentStatusEnum::CANCELED);
            $payment->getBooking()?->cancel(BookingStatusEnum::CANCELED_PAYMENT_FAILED);
            $payment->getMembership()?->cancel(MembershipStatusEnum::CANCELED_PAYMENT_FAILED);

            $this->em->flush();

            $this->paymentLogger->info(
                'payment.cancel.succeeded',
                $this->paymentEventContext($payment, 'cancel', 'succeeded')
            );

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
     * @throws ApiErrorException
     */
    public function refundPaymentViaStripe(Payment $payment): void
    {
        $this->stripeService->refund($payment);
        $this->refundPayment($payment);
    }

    /**
     * @throws Throwable
     * @throws ApiErrorException
     */
    public function cancelPaymentWithStripeIntent(Payment $payment): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            return;
        }

        $this->stripeService->cancelPaymentIntent($payment);
        $this->cancelPayment($payment);
    }

    public function cancelExpiredPayments(): void
    {
        $payments = $this->paymentRepo->findExpiredPending();

        $now = new DateTimeImmutable();

        foreach ($payments as $payment) {
            if ($payment->getExpiresAt() < $now) {
                $payment->setStatus(PaymentStatusEnum::CANCELED);
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
