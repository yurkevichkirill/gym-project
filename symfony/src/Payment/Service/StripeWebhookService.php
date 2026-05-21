<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Payment\Exception\PaymentNotFoundException;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;
use UnexpectedValueException;

final readonly class StripeWebhookService
{
    public function __construct(
        private string $webhookSecret,
        private PaymentSettlementService $paymentSettlementService,
        private LoggerInterface $logger,
    ) {}

    /**
     * @throws BadRequestHttpException
     * @throws Throwable
     */
    public function handle(string $payload, ?string $signature): void
    {
        if ($signature === null) {
            throw new BadRequestHttpException('Missing Stripe signature');
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $this->webhookSecret
            );
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            throw new BadRequestHttpException('Invalid Stripe webhook', $e);
        }

        $this->handleEvent($event);
    }

    /**
     * @throws Throwable
     */
    private function handleEvent(Event $event): void
    {
        $paymentIntentId = $event->data->object->id ?? '';

        if ($paymentIntentId === '') {
            $this->logger->warning('Stripe webhook received without PaymentIntent ID', ['type' => $event->type]);
            return;
        }

        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->paymentSettlementService->handleStripeSuccess($paymentIntentId);
                    break;
                case 'payment_intent.payment_failed':
                    $this->paymentSettlementService->failPaymentByStripeIntentId($paymentIntentId);
                    break;
                case 'payment_intent.canceled':
                    $this->paymentSettlementService->cancelPaymentByStripeIntentId($paymentIntentId);
                    break;
            }
        } catch (PaymentNotFoundException $e) {
            $this->logger->warning('Stripe webhook payment record not found', [
                'type' => $event->type,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('Stripe webhook warning', [
                'type' => $event->type,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
