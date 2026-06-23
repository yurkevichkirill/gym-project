<?php

declare(strict_types=1);

namespace App\Payment\Handler;

use App\Payment\Message\RefundPaymentMessage;
use App\Payment\Service\StripeRefundSettlementService;
use App\Payment\Service\StripeService;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
final readonly class RefundPaymentMessageHandler
{
    public function __construct(
        private StripeService $stripeService,
        private StripeRefundSettlementService $stripeRefundSettlementService,
    ) {}

    /**
     * @throws ApiErrorException
     * @throws Throwable
     */
    public function __invoke(RefundPaymentMessage $message): void
    {
        $idempotencyKey = 'reconcile_refund_' . $message->paymentId;

        $refundStatus = $this->stripeService->refundPaymentIntent(
            $message->intentId,
            $idempotencyKey,
        );

        if ($refundStatus === 'succeeded') {
            $this->stripeRefundSettlementService->handleSucceeded($message->intentId);
        } elseif (in_array($refundStatus, ['failed', 'canceled'], true)) {
            $this->stripeRefundSettlementService->handleFailed($message->intentId);
        } elseif ($refundStatus === 'requires_action') {
            $this->stripeRefundSettlementService->handleActionRequired($message->intentId);
        }
    }
}
