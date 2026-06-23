<?php

declare(strict_types=1);

namespace App\Tests\Payment\Service;

use App\Client\Entity\Client;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Repository\PaymentRepository;
use App\Payment\Service\StripeRefundSettlementService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StripeRefundSettlementServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private StripeRefundSettlementService $service;
    private PaymentRepository $paymentRepository;

    /** @var list<int> */
    private array $clientIds = [];

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(StripeRefundSettlementService::class);
        $this->paymentRepository = $container->get(PaymentRepository::class);
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $connection = $this->entityManager->getConnection();

            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            foreach ($this->clientIds as $clientId) {
                $connection->executeStatement(
                    'DELETE FROM payment WHERE client_id = :clientId AND is_refund = true',
                    ['clientId' => $clientId],
                );
                $connection->executeStatement(
                    'DELETE FROM payment WHERE client_id = :clientId',
                    ['clientId' => $clientId],
                );
                $connection->executeStatement(
                    'DELETE FROM "user" WHERE id = :clientId',
                    ['clientId' => $clientId],
                );
            }

            $this->entityManager->close();
        }

        parent::tearDown();
    }

    public function testFullTopUpRefundReversesInternalCreditAndCreatesRefundRecord(): void
    {
        $client = $this->persistClient(1500);
        $payment = $this->persistTopUpPayment(
            $client,
            'pi_full_top_up_refund_' . bin2hex(random_bytes(4)),
            PaymentStatusEnum::REFUND_PENDING,
        );

        $this->service->handleSucceeded(
            $payment->getStripePaymentIntentId() ?? '',
            $payment->getAmount(),
        );
        $this->entityManager->refresh($client);

        self::assertSame(500, $client->getBalance());
        self::assertSame(PaymentStatusEnum::REFUNDED, $payment->getStatus());

        $refundPayment = $this->paymentRepository->findRefundForOriginalPayment($payment);
        self::assertNotNull($refundPayment);
        self::assertTrue($refundPayment->getIsRefund());
        self::assertSame(1000, $refundPayment->getAmount());
        self::assertSame(PaymentMethodEnum::CARD, $refundPayment->getMethod());
        self::assertSame(PaymentStatusEnum::SUCCEEDED, $refundPayment->getStatus());
    }

    public function testCumulativePartialTopUpRefundOnlyReversesNewAmount(): void
    {
        $client = $this->persistClient(1500);
        $payment = $this->persistTopUpPayment(
            $client,
            'pi_partial_top_up_refund_' . bin2hex(random_bytes(4)),
            PaymentStatusEnum::SUCCEEDED,
        );
        $intentId = $payment->getStripePaymentIntentId() ?? '';

        $this->service->handleChargeRefunded($intentId, 400);
        $this->service->handleChargeRefunded($intentId, 400);
        $this->service->handleChargeRefunded($intentId, 700);
        $this->service->handleChargeRefunded($intentId, 500);
        $this->entityManager->refresh($client);

        self::assertSame(800, $client->getBalance());
        self::assertSame(PaymentStatusEnum::SUCCEEDED, $payment->getStatus());

        $refundPayment = $this->paymentRepository->findRefundForOriginalPayment($payment);
        self::assertNotNull($refundPayment);
        self::assertSame(700, $refundPayment->getAmount());
        self::assertSame(PaymentStatusEnum::SUCCEEDED, $refundPayment->getStatus());
    }

    public function testFullTopUpRefundAfterPartialOnlyReversesRemainingCredit(): void
    {
        $client = $this->persistClient(1500);
        $payment = $this->persistTopUpPayment(
            $client,
            'pi_full_after_partial_refund_' . bin2hex(random_bytes(4)),
            PaymentStatusEnum::SUCCEEDED,
        );
        $intentId = $payment->getStripePaymentIntentId() ?? '';

        $this->service->handleChargeRefunded($intentId, 400);
        $this->service->handleChargeRefunded($intentId, 1000);
        $this->entityManager->refresh($client);

        self::assertSame(500, $client->getBalance());
        self::assertSame(PaymentStatusEnum::REFUNDED, $payment->getStatus());

        $refundPayment = $this->paymentRepository->findRefundForOriginalPayment($payment);
        self::assertNotNull($refundPayment);
        self::assertSame(1000, $refundPayment->getAmount());
    }

    public function testLateSuccessAfterCancellationDoesNotReverseUncreditedBalance(): void
    {
        $client = $this->persistClient(500);
        $payment = $this->persistTopUpPayment(
            $client,
            'pi_late_success_after_cancel_' . bin2hex(random_bytes(4)),
            PaymentStatusEnum::CANCELED,
        );
        $intentId = $payment->getStripePaymentIntentId() ?? '';

        $this->service->markPending($intentId);
        self::assertSame(PaymentStatusEnum::REFUND_PENDING, $payment->getStatus());

        $this->service->handleSucceeded($intentId, $payment->getAmount());
        $this->entityManager->refresh($client);

        self::assertSame(500, $client->getBalance());
        self::assertSame(PaymentStatusEnum::REFUNDED, $payment->getStatus());
        self::assertNotNull($this->paymentRepository->findRefundForOriginalPayment($payment));
    }

    public function testFailedLatePaymentRefundRemainsVisibleInLifecycle(): void
    {
        $client = $this->persistClient(500);
        $payment = $this->persistTopUpPayment(
            $client,
            'pi_failed_late_refund_' . bin2hex(random_bytes(4)),
            PaymentStatusEnum::FAILED,
        );
        $intentId = $payment->getStripePaymentIntentId() ?? '';

        $this->service->markPending($intentId);
        $this->service->handleFailed($intentId);

        self::assertSame(PaymentStatusEnum::REFUND_FAILED, $payment->getStatus());
        self::assertSame(500, $client->getBalance());
    }

    private function persistClient(int $balance): Client
    {
        $suffix = bin2hex(random_bytes(6));
        $client = new Client();
        $client->setEmail("stripe_refund_{$suffix}@example.com");
        $client->setFirstName('Stripe');
        $client->setLastName('Refund');
        $client->setPhone('+37529' . random_int(1000000, 9999999));
        $client->setPassword('password');
        $client->setAge(30);
        $client->setBalance($balance);
        $client->setIsActive(true);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $clientId = $client->getId();
        self::assertIsInt($clientId);
        $this->clientIds[] = $clientId;

        return $client;
    }

    private function persistTopUpPayment(
        Client $client,
        string $intentId,
        PaymentStatusEnum $status,
    ): Payment {
        $payment = new Payment(PaymentMethodEnum::CARD);
        $payment->setClient($client);
        $payment->setAmount(1000);
        $payment->setCategory(PaymentCategoryEnum::BALANCE_TOP_UP);
        $payment->setStatus($status);
        $payment->setStripePaymentIntentId($intentId);

        if (in_array($status, [
            PaymentStatusEnum::SUCCEEDED,
            PaymentStatusEnum::REFUND_PENDING,
            PaymentStatusEnum::REFUND_FAILED,
        ], true)) {
            $payment->setPaidAt(new DateTimeImmutable());
        }

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }
}
