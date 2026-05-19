<?php

declare(strict_types=1);

namespace App\Payment\Service;

use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    private function handleEvent(Event $event): void
    {
        $paymentIntentId = $event->data->object->id ?? '';

        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    if ($paymentIntentId !== '') {
                        $this->paymentSettlementService->handleStripeSuccess($paymentIntentId);
                    }
                    break;
                case 'payment_intent.payment_failed':
                    if ($paymentIntentId !== '') {
                        $this->paymentSettlementService->failPaymentByStripeIntentId($paymentIntentId);
                    }
                    break;
                case 'payment_intent.canceled':
                    if ($paymentIntentId !== '') {
                        $this->paymentSettlementService->cancelPaymentByStripeIntentId($paymentIntentId);
                    }
                    break;
            }
        } catch (NotFoundHttpException $e) {
            $this->logger->warning('Stripe webhook payment record not found', [
                'type' => $event->type,
                'message' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('Stripe webhook warning', [
                'type' => $event->type,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
