<?php

declare(strict_types=1);

namespace App\Tests\Payment\Service;

use App\Payment\Service\PaymentSettlementService;
use App\Payment\Service\StripeRefundSettlementService;
use App\Payment\Service\StripeWebhookService;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use ReflectionClass;
use ReflectionMethod;
use Stripe\Event;

final class StripeWebhookServiceTest extends TestCase
{
    public function testPaymentFailedEventKeepsLocalPaymentPending(): void
    {
        $settlementService = (new ReflectionClass(PaymentSettlementService::class))->newInstanceWithoutConstructor();
        $refundSettlementService = (new ReflectionClass(StripeRefundSettlementService::class))->newInstanceWithoutConstructor();
        $logger = new InMemoryLogger();
        $webhookService = new StripeWebhookService(
            'unused',
            $settlementService,
            $refundSettlementService,
            $logger,
        );

        $this->handleEvent($webhookService, Event::constructFrom([
            'id' => 'evt_payment_failed',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_declined_then_retried',
                    'object' => 'payment_intent',
                    'status' => 'requires_payment_method',
                ],
            ],
        ]));

        self::assertCount(1, $logger->records);
        self::assertSame('info', $logger->records[0]['level']);
        self::assertSame(
            'Stripe PaymentIntent payment attempt failed; keeping local payment pending',
            $logger->records[0]['message'],
        );
        self::assertSame('pi_declined_then_retried', $logger->records[0]['context']['intent_id'] ?? null);
    }

    private function handleEvent(StripeWebhookService $service, Event $event): void
    {
        $method = new ReflectionMethod($service, 'handleEvent');
        $method->invoke($service, $event);
    }
}

final class InMemoryLogger extends AbstractLogger
{
    /**
     * @var list<array{level: mixed, message: string|\Stringable, context: array<mixed>}>
     */
    public array $records = [];

    /**
     * @param array<mixed> $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }
}
