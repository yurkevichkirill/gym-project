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
        private StripeRefundSettlementService $stripeRefundSettlementService,
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
                $this->webhookSecret,
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
        $paymentIntentId = match ($event->type) {
            'charge.refunded',
            'charge.dispute.created',
            'charge.dispute.funds_reinstated',
            'refund.failed',
            'refund.updated' => $this->stripeObjectStringValue($event, 'payment_intent'),
            default => $this->stripeObjectStringValue($event, 'id'),
        };

        if ($paymentIntentId === '') {
            $this->logger->warning('Stripe webhook received without PaymentIntent ID', [
                'type' => $event->type,
            ]);

            return;
        }

        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->paymentSettlementService->handleStripeSuccess($paymentIntentId);
                    break;
                case 'payment_intent.payment_failed':
                    $this->logger->info('Stripe PaymentIntent payment attempt failed; keeping local payment pending', [
                        'intent_id' => $paymentIntentId,
                    ]);
                    break;
                case 'payment_intent.canceled':
                    $this->paymentSettlementService->cancelPaymentByStripeIntentId($paymentIntentId);
                    break;
                case 'charge.refunded':
                    $amountRefunded = $this->stripeObjectIntValue($event, 'amount_refunded');
                    if ($amountRefunded === null) {
                        $this->logger->warning('Stripe charge.refunded received without amount_refunded', [
                            'intent_id' => $paymentIntentId,
                        ]);
                        break;
                    }

                    $this->stripeRefundSettlementService->handleSucceeded(
                        $paymentIntentId,
                        $amountRefunded,
                    );
                    break;
                case 'charge.dispute.created':
                    $this->stripeRefundSettlementService->handleDisputeCreated($paymentIntentId);
                    break;
                case 'charge.dispute.funds_reinstated':
                    $this->stripeRefundSettlementService->handleDisputeFundsReinstated($paymentIntentId);
                    break;
                case 'refund.failed':
                    $this->stripeRefundSettlementService->handleFailed($paymentIntentId);
                    break;
                case 'refund.updated':
                    $this->handleRefundUpdated($event, $paymentIntentId);
                    break;
            }
        } catch (PaymentNotFoundException $e) {
            $this->logger->warning('Stripe webhook payment record not found; acknowledging event', [
                'type' => $event->type,
                'intent_id' => $paymentIntentId,
                'message' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Stripe webhook warning', [
                'type' => $event->type,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function handleRefundUpdated(Event $event, string $paymentIntentId): void
    {
        $refundStatus = $this->stripeObjectStringValue($event, 'status');

        if ($refundStatus === 'succeeded') {
            $refundAmount = $this->stripeObjectIntValue($event, 'amount');
            if ($refundAmount === null) {
                $this->logger->warning('Stripe refund.updated received without amount', [
                    'intent_id' => $paymentIntentId,
                ]);

                return;
            }

            $this->stripeRefundSettlementService->handleSucceeded(
                $paymentIntentId,
                $refundAmount,
            );

            return;
        }

        if (in_array($refundStatus, ['failed', 'canceled'], true)) {
            $this->stripeRefundSettlementService->handleFailed($paymentIntentId);

            return;
        }

        if ($refundStatus === 'requires_action') {
            $this->stripeRefundSettlementService->handleActionRequired($paymentIntentId);
        }
    }

    private function stripeObjectStringValue(Event $event, string $key): string
    {
        $value = $event->data->object[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    private function stripeObjectIntValue(Event $event, string $key): ?int
    {
        $value = $event->data->object[$key] ?? null;

        return is_int($value) ? $value : null;
    }
}
