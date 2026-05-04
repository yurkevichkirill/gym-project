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
    ) {
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

        $idempotencyKey = sprintf('pi_%d', $payment->getId());

        try {
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
                'idempotency_key' => $idempotencyKey,
            ]);

            $payment->setStripePaymentIntentId($intent->id);
            $this->em->flush();

            return $intent->client_secret;

        } catch (Throwable $e) {
            $this->stripeLogger->error('stripe.intent.failed', [
                'payment_id' => $payment->getId(),
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
            ]);

            throw $e;
        }
    }
}
