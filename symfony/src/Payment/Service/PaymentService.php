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
use DateMalformedStringException;
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

    /**
     * @throws DateMalformedStringException
     */
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
        $payment->setExpiresAt(
            new DateTimeImmutable()->modify('+15 minutes')
        );

        if ($trainer) {
            $payment->setTrainer($trainer);
        }

        $this->paymentRepo->create($payment);

        $this->paymentLogger->info('Payment created', $this->paymentContext($payment, 'create', 'succeeded', [
            'amount' => $amount,
            'expires_at' => $payment->getExpiresAt()?->format(DATE_ATOM),
        ]));

        return $payment;
    }

    public function confirmPayment(Payment $payment, ?Membership $membership = null, ?Booking $booking = null): void
    {
        $context = $this->paymentContext($payment, 'confirm', 'started', [
            'target_booking_id' => $booking?->getId(),
            'target_membership_id' => $membership?->getId(),
        ]);

        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            $this->paymentLogger->notice('Payment confirmation skipped: payment is not pending', $this->paymentContext($payment, 'confirm', 'skipped', [
                'target_booking_id' => $booking?->getId(),
                'target_membership_id' => $membership?->getId(),
            ]));

            return;
        }

        $amount = $payment->getAmount();
        if ($amount === null) {
            throw new LogicException('Payment amount is not set');
        }

        $this->paymentLogger->info('Payment confirmation started', $context + [
            'amount' => $amount,
        ]);

        try {
            match ($payment->getCategory()) {
                PaymentCategoryEnum::TRAINER => $this->confirmBookingPayment($payment, $amount, $booking),
                PaymentCategoryEnum::MEMBERSHIP => $this->confirmMembershipPayment($payment, $amount, $membership),
                PaymentCategoryEnum::BALANCE_TOP_UP => $this->confirmTopUpPayment($payment, $amount),
            };
        } catch (Throwable $e) {
            $this->paymentLogger->error('Payment confirmation failed', $this->paymentContext($payment, 'confirm', 'failed', [
                'target_booking_id' => $booking?->getId(),
                'target_membership_id' => $membership?->getId(),
                'amount' => $amount,
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]));

            throw $e;
        }

        $payment->setStatus(PaymentStatusEnum::SUCCEEDED);
        $payment->setConfirmedAt(new DateTimeImmutable());
        if ($payment->getPaidAt() === null) {
            $payment->setPaidAt(new DateTimeImmutable());
        }
        $payment->setExpiresAt(null);

        $this->em->flush();

        $this->paymentLogger->info('Payment confirmed', $this->paymentContext($payment, 'confirm', 'succeeded', [
            'target_booking_id' => $booking?->getId(),
            'target_membership_id' => $membership?->getId(),
            'amount' => $amount,
        ]));
    }

    private function confirmBookingPayment(Payment $payment, int $amount, ?Booking $booking = null): void
    {
        $client = $payment->getClient();
        switch ($payment->getMethod()) {
            case PaymentMethodEnum::BALANCE:
                $client?->setBalance(($client->getBalance() - $amount));
                break;

            case PaymentMethodEnum::CARD:
                break;
        }

        if ($trainer = $payment->getTrainer()) {
            $trainer->setBalance($trainer->getBalance() + $amount);
        }

        $booking ??= $payment->getBooking();
        if ($booking === null) {
            throw new LogicException('Booking payment cannot be confirmed without booking');
        }

        $booking->confirm();
    }

    private function confirmMembershipPayment(Payment $payment, int $amount, ?Membership $membership = null): void
    {
        $client = $payment->getClient();
        switch ($payment->getMethod()) {
            case PaymentMethodEnum::BALANCE:
                $client?->setBalance(($client->getBalance() - $amount));
                break;

            case PaymentMethodEnum::CARD:
                break;
        }

        $membership ??= $payment->getMembership();
        if ($membership === null) {
            throw new LogicException('Membership payment cannot be confirmed without membership');
        }

        $membership->activate();
    }

    private function confirmTopUpPayment(Payment $payment, int $amount): void
    {
        $client = $payment->getClient();
        $client?->setBalance(($client->getBalance() + $amount));
    }

    public function confirmPaymentByStripeIntentId(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            $this->paymentLogger->warning('Payment confirmation by Stripe intent failed: payment not found', [
                'domain' => 'payment',
                'operation' => 'confirm_by_intent',
                'outcome' => 'failed',
                'stripe_payment_intent_id' => $intentId,
            ]);
            throw new NotFoundHttpException('Payment for Stripe intent was not found');
        }

        $this->confirmPayment($payment);
    }

    public function failPayment(Payment $payment): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            $this->paymentLogger->notice('Payment fail skipped: payment is not pending', $this->paymentContext($payment, 'fail', 'skipped'));
            return;
        }

        $payment->setStatus(PaymentStatusEnum::FAILED);
        $payment->getBooking()?->cancel(BookingStatusEnum::CANCELED_PAYMENT_FAILED);
        $payment->getMembership()?->cancel(MembershipStatusEnum::CANCELED_PAYMENT_FAILED);

        $this->em->flush();

        $this->paymentLogger->warning('Payment marked as failed', $this->paymentContext($payment, 'fail', 'failed'));
    }

    public function failPaymentByStripeIntentId(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            $this->paymentLogger->warning('Payment failure by Stripe intent failed: payment not found', [
                'domain' => 'payment',
                'operation' => 'fail_by_intent',
                'outcome' => 'failed',
                'stripe_payment_intent_id' => $intentId,
            ]);
            throw new NotFoundHttpException('Payment for Stripe intent was not found');
        }

        $this->failPayment($payment);
    }

    public function refundPayment(Payment $payment): void
    {
        if ($payment->getStatus() === PaymentStatusEnum::REFUNDED) {
            $this->paymentLogger->notice('Payment refund skipped: payment already refunded', $this->paymentContext($payment, 'refund', 'skipped'));

            return;
        }

        if ($payment->getStatus() !== PaymentStatusEnum::SUCCEEDED) {
            throw new LogicException('Cannot refund unpaid payment');
        }

        $client = $payment->getClient();
        $amount = $payment->getAmount();
        if ($amount === null) {
            throw new LogicException('Payment amount is not set');
        }

        $client?->setBalance(($client->getBalance() + $amount));

        if ($payment->getTrainer()) {
            $trainer = $payment->getTrainer();
            $trainer->setBalance($trainer->getBalance() - $amount);
        }

        $payment->setIsRefund(true);
        $payment->setStatus(PaymentStatusEnum::REFUNDED);

        $this->em->flush();

        $this->paymentLogger->info('Payment refunded', $this->paymentContext($payment, 'refund', 'succeeded', [
            'amount' => $amount,
        ]));
    }

    public function refundPaymentByStripeIntentId(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            $this->paymentLogger->warning('Payment refund by Stripe intent failed: payment not found', [
                'domain' => 'payment',
                'operation' => 'refund_by_intent',
                'outcome' => 'failed',
                'stripe_payment_intent_id' => $intentId,
            ]);
            throw new NotFoundHttpException('Payment for Stripe intent was not found');
        }

        $this->refundPayment($payment);
    }

    /**
     * @throws ApiErrorException|Throwable
     */
    public function refundPaymentViaStripe(Payment $payment): void
    {
        $this->paymentLogger->info('Payment refund via Stripe started', $this->paymentContext($payment, 'refund_via_stripe', 'started'));
        $this->stripeService->refund($payment);
        $this->refundPayment($payment);
    }

    public function cancelPayment(Payment $payment): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            $this->paymentLogger->notice('Payment cancel skipped: payment is not pending', $this->paymentContext($payment, 'cancel', 'skipped'));
            return;
        }

        $payment->setStatus(PaymentStatusEnum::CANCELED);

        $payment->getBooking()?->cancel(BookingStatusEnum::CANCELED_PAYMENT_FAILED);
        $payment->getMembership()?->cancel(MembershipStatusEnum::CANCELED_PAYMENT_FAILED);

        $this->em->flush();

        $this->paymentLogger->info('Payment canceled', $this->paymentContext($payment, 'cancel', 'succeeded'));
    }

    /**
     * @throws ApiErrorException|Throwable
     */
    public function cancelPaymentWithStripeIntent(Payment $payment): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            $this->paymentLogger->notice('Payment cancel via Stripe skipped: payment is not pending', $this->paymentContext($payment, 'cancel_via_stripe', 'skipped'));

            return;
        }

        $this->paymentLogger->info('Payment cancel via Stripe started', $this->paymentContext($payment, 'cancel_via_stripe', 'started'));
        $this->stripeService->cancelPaymentIntent($payment);
        $this->cancelPayment($payment);
    }

    public function cancelExpiredPayments(): void
    {
        $payments = $this->paymentRepo->findExpiredPending();

        $this->paymentLogger->info('Expired pending payment cleanup started', [
            'domain' => 'payment',
            'operation' => 'cleanup_expired',
            'outcome' => 'started',
            'expired_payments_count' => count($payments),
        ]);

        foreach ($payments as $payment) {
            $this->cancelPayment($payment);
        }

        $this->paymentLogger->info('Expired pending payment cleanup finished', [
            'domain' => 'payment',
            'operation' => 'cleanup_expired',
            'outcome' => 'succeeded',
            'expired_payments_count' => count($payments),
        ]);
    }

    private function paymentContext(Payment $payment, string $operation, string $outcome, array $extra = []): array
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
            'stripe_payment_intent_id' => $payment->getStripePaymentIntentId(),
            'currency' => $payment->getCurrency(),
            'is_refund' => $payment->getIsRefund(),
        ];
    }
}
