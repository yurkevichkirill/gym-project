<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Payment\Repository\PaymentRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnexpectedValueException;

final readonly class StripeWebhookService
{
    public function __construct(
        private string $webhookSecret,
        private PaymentService $paymentService,
        private PaymentRepository $paymentRepo,
        private LoggerInterface $logger,
    ) {}

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
        $paymentIntentId = (string) ($event->data->object->id ?? '');

        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    if ($paymentIntentId !== '') {
                        $payment = $this->paymentRepo->findOneByStripePaymentIntentId($paymentIntentId);
                        $payment->setPaidAt(new DateTimeImmutable());
                        $this->paymentService->confirmPaymentByStripeIntentId($paymentIntentId);
                    }
                    break;
                case 'payment_intent.payment_failed':
                    if ($paymentIntentId !== '') {
                        $this->paymentService->failPaymentByStripeIntentId($paymentIntentId);
                    }
                    break;
                case 'payment_intent.canceled':
                    if ($paymentIntentId !== '') {
                        $this->paymentService->failPaymentByStripeIntentId($paymentIntentId);
                    }
                    break;
                case 'charge.refunded':
                    $refundIntentId = (string) ($event->data->object->payment_intent ?? '');
                    if ($refundIntentId !== '') {
                        $this->paymentService->refundPaymentByStripeIntentId($refundIntentId);
                    }
                    break;
                default:
                    $this->logger->info('Unhandled Stripe webhook event', [
                        'type' => $event->type,
                    ]);
            }
        } catch (NotFoundHttpException $e) {
            $this->logger->warning('Stripe webhook payment record not found', [
                'type' => $event->type,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
