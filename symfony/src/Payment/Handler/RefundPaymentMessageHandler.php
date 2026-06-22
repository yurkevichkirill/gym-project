<?php

declare(strict_types=1);

namespace App\Payment\Handler;

use App\Payment\Message\RefundPaymentMessage;
use App\Payment\Service\PaymentSettlementService;
use App\Payment\Service\StripeService;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
final readonly class RefundPaymentMessageHandler
{
    public function __construct(
        private StripeService $stripeService,
        private PaymentSettlementService $paymentSettlementService,
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
            $idempotencyKey
        );

        if ($refundStatus === 'succeeded') {
            $this->paymentSettlementService->handleStripeRefundSucceeded($message->intentId);
        } elseif ($refundStatus === 'failed') {
            $this->paymentSettlementService->handleStripeRefundFailed($message->intentId);
        }
    }
}
