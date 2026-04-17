<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Payment\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class StripeService
{
    private StripeClient $stripe;

    public function __construct(
        string $stripeSecretKey,
        private EntityManagerInterface $em
    )
    {
        $this->stripe = new StripeClient($stripeSecretKey);
    }

    /**
     * @throws ApiErrorException
     */
    public function createPaymentIntent(Payment $payment): string
    {
        if ($payment->getId() === null || $payment->getAmount() === null) {
            throw new BadRequestHttpException('Payment must be persisted before creating PaymentIntent');
        }

        $intent = $this->stripe->paymentIntents->create([
            'amount' => $payment->getAmount(),
            'currency' => $payment->getCurrency(),

            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],

            'metadata' => [
                'payment_id' => (string) $payment->getId(),
            ],
        ], [
            'idempotency_key' => sprintf('payment_intent_%d', $payment->getId()),
        ]);

        $payment->setStripePaymentIntentId($intent->id);
        $this->em->flush();

        return $intent->client_secret;
    }

    /**
     * @throws ApiErrorException
     */
    public function refund(Payment $payment): void
    {
        if ($payment->getStripePaymentIntentId() === null) {
            throw new BadRequestHttpException('Payment has no Stripe PaymentIntent');
        }

        $this->stripe->refunds->create([
            'payment_intent' => $payment->getStripePaymentIntentId(),
        ], [
            'idempotency_key' => sprintf('payment_refund_%d', $payment->getId()),
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function cancelPaymentIntent(Payment $payment): void
    {
        if ($payment->getStripePaymentIntentId() === null) {
            return;
        }

        $this->stripe->paymentIntents->cancel(
            $payment->getStripePaymentIntentId()
        );
    }
}
