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
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepo,
        private StripeService $stripeService,
        private EntityManagerInterface $em,
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

        return $payment;
    }

    public function confirmPayment(Payment $payment, ?Membership $membership = null, ?Booking $booking = null): void
    {
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
        if ($payment->getPaidAt() === null) {
            $payment->setPaidAt(new DateTimeImmutable());
        }
        $payment->setExpiresAt(null);

        $this->em->flush();
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

        if ($booking !== null) {
            $booking->confirm();
        } else {
            $payment->getBooking()->confirm();
        }
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

        if ($membership !== null) {
            $membership->activate();
        } else {
            $payment->getMembership()->activate();
        }
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
            throw new NotFoundHttpException('Payment for Stripe intent was not found');
        }

        $this->confirmPayment($payment);
    }

    public function failPayment(Payment $payment): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            return;
        }

        $payment->setStatus(PaymentStatusEnum::FAILED);

        $payment->getBooking()?->cancel(BookingStatusEnum::CANCELED_PAYMENT_FAILED);
        $payment->getMembership()?->cancel(MembershipStatusEnum::CANCELED_PAYMENT_FAILED);

        $this->em->flush();
    }

    public function failPaymentByStripeIntentId(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new NotFoundHttpException('Payment for Stripe intent was not found');
        }

        $this->failPayment($payment);
    }

    public function refundPayment(Payment $payment): void
    {
        if ($payment->getStatus() === PaymentStatusEnum::REFUNDED) {
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
    }

    public function refundPaymentByStripeIntentId(string $intentId): void
    {
        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($intentId);
        if ($payment === null) {
            throw new NotFoundHttpException('Payment for Stripe intent was not found');
        }

        $this->refundPayment($payment);
    }

    /**
     * @throws ApiErrorException
     */
    public function refundPaymentViaStripe(Payment $payment): void
    {
        $this->stripeService->refund($payment);
        $this->refundPayment($payment);
    }

    public function cancelPayment(Payment $payment): void
    {
        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            return;
        }

        $payment->setStatus(PaymentStatusEnum::CANCELED);

        $payment->getBooking()?->cancel(BookingStatusEnum::CANCELED_PAYMENT_FAILED);
        $payment->getMembership()?->cancel(MembershipStatusEnum::CANCELED_PAYMENT_FAILED);

        $this->em->flush();
    }

    /**
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

        foreach ($payments as $payment) {
            $this->cancelPayment($payment);
        }
    }
}
