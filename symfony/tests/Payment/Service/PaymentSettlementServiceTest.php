<?php

declare(strict_types=1);

namespace App\Tests\Payment\Service;

use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Message\RefundPaymentMessage;
use App\Payment\Service\PaymentLifecycleService;
use App\Payment\Service\PaymentSettlementService;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use ReflectionClass;
use ReflectionMethod;

final class PaymentSettlementServiceTest extends TestCase
{
    public function testSucceededStripePaymentForProcessedMembershipIsCanceledAndRefunded(): void
    {
        $payment = new Payment(PaymentMethodEnum::CARD);
        $payment->setStatus(PaymentStatusEnum::PENDING);
        $payment->setCategory(PaymentCategoryEnum::MEMBERSHIP);
        $payment->setAmount(1000);
        $payment->setStripePaymentIntentId('pi_processed_membership');
        $this->setPrivateProperty($payment, 'id', 123);

        $membership = new Membership();
        $membership->setStatus(MembershipStatusEnum::ACTIVE);
        $membership->setPayment($payment);
        $payment->setMembership($membership);
        $this->setPrivateProperty($membership, 'id', 456);

        $logger = new InMemoryPaymentLogger();
        $service = (new ReflectionClass(PaymentSettlementService::class))->newInstanceWithoutConstructor();
        $this->setPrivateProperty($service, 'paymentLifecycleService', new PaymentLifecycleService());
        $this->setPrivateProperty($service, 'paymentLogger', $logger);

        $message = $this->refundSucceededStripePaymentForProcessedMembership(
            $service,
            $payment,
            $membership,
            'pi_processed_membership',
        );

        self::assertSame(PaymentStatusEnum::CANCELED, $payment->getStatus());
        self::assertSame(MembershipStatusEnum::ACTIVE, $membership->getStatus());
        self::assertSame(123, $message->paymentId);
        self::assertSame('pi_processed_membership', $message->intentId);
        self::assertSame('critical', $logger->records[0]['level'] ?? null);
        self::assertSame('initiating_stripe_refund', $logger->records[0]['context']['action'] ?? null);
    }

    public function testCardRefundRequestsStripeRefundAndKeepsInternalBalanceUntouched(): void
    {
        $payment = new Payment(PaymentMethodEnum::CARD);
        $payment->setStatus(PaymentStatusEnum::SUCCEEDED);
        $payment->setCategory(PaymentCategoryEnum::TRAINER);
        $payment->setAmount(1000);
        $payment->setStripePaymentIntentId('pi_card_refund');
        $this->setPrivateProperty($payment, 'id', 321);

        $logger = new InMemoryPaymentLogger();
        $service = (new ReflectionClass(PaymentSettlementService::class))->newInstanceWithoutConstructor();
        $this->setPrivateProperty($service, 'paymentLifecycleService', new PaymentLifecycleService());
        $this->setPrivateProperty($service, 'paymentLogger', $logger);

        $message = $this->requestStripeCardRefund($service, $payment);

        self::assertSame(PaymentStatusEnum::REFUND_PENDING, $payment->getStatus());
        self::assertSame(321, $message->paymentId);
        self::assertSame('pi_card_refund', $message->intentId);
        self::assertSame('info', $logger->records[0]['level'] ?? null);
        self::assertSame('pi_card_refund', $logger->records[0]['context']['intent_id'] ?? null);
    }

    private function requestStripeCardRefund(
        PaymentSettlementService $service,
        Payment $payment,
    ): RefundPaymentMessage {
        $method = new ReflectionMethod($service, 'requestStripeCardRefund');

        /** @var RefundPaymentMessage $message */
        $message = $method->invoke($service, $payment);

        return $message;
    }

    private function refundSucceededStripePaymentForProcessedMembership(
        PaymentSettlementService $service,
        Payment $payment,
        Membership $membership,
        string $intentId,
    ): RefundPaymentMessage {
        $method = new ReflectionMethod($service, 'refundSucceededStripePaymentForProcessedMembership');

        /** @var RefundPaymentMessage $message */
        $message = $method->invoke($service, $payment, $membership, $intentId);

        return $message;
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflectionProperty = new \ReflectionProperty($object, $property);
        $reflectionProperty->setValue($object, $value);
    }
}

final class InMemoryPaymentLogger extends AbstractLogger
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
