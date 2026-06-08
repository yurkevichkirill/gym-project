<?php

declare(strict_types=1);

namespace App\Payment\Handler;

use App\Payment\Message\RefundPaymentMessage;
use App\Payment\Service\StripeService;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RefundPaymentMessageHandler
{
    public function __construct(
        private StripeService $stripeService,
    ) {}

    /**
     * @throws ApiErrorException
     */
    public function __invoke(RefundPaymentMessage $message): void
    {
        $idempotencyKey = 'reconcile_refund_' . $message->paymentId;

        $this->stripeService->refundPaymentIntent(
            $message->intentId,
            $idempotencyKey
        );
    }
}
