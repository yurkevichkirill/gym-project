<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Payment\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

final readonly class StripeService
{
    private StripeClient $stripe;

    public function __construct(
        string $stripeSecretKey,
        private EntityManagerInterface $em,
        private LoggerInterface $stripeLogger,
    )
    {
        $this->stripe = new StripeClient($stripeSecretKey);
    }

    /**
     * @throws ApiErrorException
     * @throws Throwable
     */
    public function createPaymentIntent(Payment $payment): string
    {
        if ($payment->getId() === null || $payment->getAmount() === null) {
            throw new BadRequestHttpException('Payment must be persisted before creating PaymentIntent');
        }

        $idempotencyKey = sprintf('payment_intent_%d', $payment->getId());

        $this->stripeLogger->info('Stripe payment intent creation started', $this->stripeContext($payment, 'create_payment_intent', 'started', [
            'idempotency_key' => $idempotencyKey,
        ]));

        try {
            $intent = $this->stripe->paymentIntents->create([
                'amount' => $payment->getAmount(),
                'currency' => $payment->getCurrency(),

                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],

                'metadata' => [
                    'payment_id' => (string)$payment->getId(),
                ],
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (Throwable $e) {
            $this->stripeLogger->error('Stripe payment intent creation failed', $this->stripeContext($payment, 'create_payment_intent', 'failed', [
                'idempotency_key' => $idempotencyKey,
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]));

            throw $e;
        }

        $payment->setStripePaymentIntentId($intent->id);

        $this->em->flush();

        $this->stripeLogger->info('Stripe payment intent created', $this->stripeContext($payment, 'create_payment_intent', 'succeeded', [
            'stripe_payment_intent_id' => $intent->id,
            'idempotency_key' => $idempotencyKey,
        ]));

        return $intent->client_secret;
    }

    /**
     * @throws ApiErrorException
     * @throws Throwable
     */
    public function refund(Payment $payment): void
    {
        if ($payment->getStripePaymentIntentId() === null) {
            $this->stripeLogger->error('Stripe refund failed: payment has no Stripe intent', $this->stripeContext($payment, 'refund', 'failed'));

            throw new BadRequestHttpException('Payment has no Stripe PaymentIntent');
        }

        $idempotencyKey = sprintf('payment_refund_%d', $payment->getId());

        $this->stripeLogger->info('Stripe refund started', $this->stripeContext($payment, 'refund', 'started', [
            'idempotency_key' => $idempotencyKey,
        ]));

        try {
            $this->stripe->refunds->create([
                'payment_intent' => $payment->getStripePaymentIntentId(),
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (Throwable $e) {
            $this->stripeLogger->error('Stripe refund failed', $this->stripeContext($payment, 'refund', 'failed', [
                'idempotency_key' => $idempotencyKey,
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]));

            throw $e;
        }

        $this->stripeLogger->info('Stripe refund completed', $this->stripeContext($payment, 'refund', 'succeeded', [
            'idempotency_key' => $idempotencyKey,
        ]));
    }

    /**
     * @throws ApiErrorException
     * @throws Throwable
     */
    public function cancelPaymentIntent(Payment $payment): void
    {
        if ($payment->getStripePaymentIntentId() === null) {
            $this->stripeLogger->notice('Stripe payment intent cancel skipped: payment has no Stripe intent', $this->stripeContext($payment, 'cancel_payment_intent', 'skipped'));
            return;
        }

        $this->stripeLogger->info('Stripe payment intent cancel started', $this->stripeContext($payment, 'cancel_payment_intent', 'started'));

        try {
            $this->stripe->paymentIntents->cancel(
                $payment->getStripePaymentIntentId()
            );
        } catch (Throwable $e) {
            $this->stripeLogger->error('Stripe payment intent cancel failed', $this->stripeContext($payment, 'cancel_payment_intent', 'failed', [
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]));
            throw $e;
        }

        $this->stripeLogger->info('Stripe payment intent canceled', $this->stripeContext($payment, 'cancel_payment_intent', 'succeeded'));
    }

    private function stripeContext(Payment $payment, string $operation, string $outcome, array $extra = []): array
    {
        return $extra + [
            'domain' => 'stripe',
            'provider' => 'stripe',
            'operation' => $operation,
            'outcome' => $outcome,
            'payment_id' => $payment->getId(),
            'client_id' => $payment->getClient()?->getId(),
            'trainer_id' => $payment->getTrainer()?->getId(),
            'booking_id' => $payment->getBooking()?->getId(),
            'membership_id' => $payment->getMembership()?->getId(),
            'category' => $payment->getCategory()?->value,
            'method' => $payment->getMethod()->value,
            'amount' => $payment->getAmount(),
            'currency' => $payment->getCurrency(),
            'stripe_payment_intent_id' => $payment->getStripePaymentIntentId(),
        ];
    }
}
