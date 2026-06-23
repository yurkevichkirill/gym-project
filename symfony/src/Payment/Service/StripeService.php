<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Payment\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\ApiRequestor;
use Stripe\Exception\ApiErrorException;
use Stripe\HttpClient\CurlClient;
use Stripe\StripeClient;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

final readonly class StripeService
{
    private const int REQUEST_TIMEOUT_SECONDS = 15;
    private const int CONNECT_TIMEOUT_SECONDS = 5;

    private StripeClient $stripe;

    public function __construct(
        string $stripeSecretKey,
        private EntityManagerInterface $em,
        private LoggerInterface $stripeLogger,
    ) {
        $httpClient = new CurlClient();
        $httpClient->setTimeout(self::REQUEST_TIMEOUT_SECONDS);
        $httpClient->setConnectTimeout(self::CONNECT_TIMEOUT_SECONDS);
        ApiRequestor::setHttpClient($httpClient);

        $this->stripe = new StripeClient([
            'api_key' => $stripeSecretKey,
            'max_network_retries' => 2,
        ]);
    }

    /**
     * @throws ApiErrorException
     * @throws Throwable
     */
    public function createPaymentIntent(Payment $payment): string
    {
        if ($payment->getId() === null) {
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

            if ($intent->client_secret === null) {
                throw new BadRequestHttpException('Stripe did not return a client secret');
            }

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

    /**
     * @throws ApiErrorException
     */
    public function cancelPaymentIntent(
        string $intentId,
        ?string $idempotencyKey = null,
    ): void {
        $options = [];

        if ($idempotencyKey !== null) {
            $options['idempotency_key'] = $idempotencyKey;
        }

        $this->stripe->paymentIntents->cancel(
            $intentId,
            [],
            $options,
        );
    }

    /**
     * @throws ApiErrorException
     */
    public function refundPaymentIntent(string $intentId, ?string $idempotencyKey = null): ?string
    {
        $params = ['payment_intent' => $intentId];
        $options = [];

        if ($idempotencyKey !== null) {
            $options['idempotency_key'] = $idempotencyKey;
        }

        $refund = $this->stripe->refunds->create($params, $options);

        return is_string($refund->status) ? $refund->status : null;
    }
}
