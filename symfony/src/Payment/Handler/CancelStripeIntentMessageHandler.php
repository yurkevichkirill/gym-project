<?php

declare(strict_types=1);

namespace App\Payment\Handler;

use App\Payment\Message\CancelStripeIntentMessage;
use App\Payment\Service\StripeService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
final readonly class CancelStripeIntentMessageHandler
{
    public function __construct(
        private StripeService $stripeService,
        private LoggerInterface $paymentLogger,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(CancelStripeIntentMessage $message): void
    {
        try {
            $this->stripeService->cancelPaymentIntent(
                    $message->intentId,
                    'cancel_payment_intent_' . $message->paymentId,
            );
        } catch (Throwable $stripeException) {
            $this->paymentLogger->warning('payment.stripe_cancel.failed', [
                'payment_id' => $message->paymentId,
                'intent_id' => $message->intentId,
                'error' => $stripeException->getMessage(),
            ]);

            throw $stripeException;
        }
    }
}
