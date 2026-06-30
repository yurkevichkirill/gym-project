<?php

declare(strict_types=1);

namespace App\Tests\Payment\Service;

use App\Client\Entity\Client;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Service\StripeRefundSettlementService;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StripeRefundSettlementRefreshTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private StripeRefundSettlementService $service;

    /** @var list<int> */
    private array $clientIds = [];

    /** @var list<int> */
    private array $userIds = [];

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(StripeRefundSettlementService::class);
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
                    'DELETE FROM payment WHERE client_id = :clientId',
                    ['clientId' => $clientId],
                );
            }

            foreach ($this->userIds as $userId) {
                $connection->executeStatement(
                    'DELETE FROM "user" WHERE id = :userId',
                    ['userId' => $userId],
                );
            }

            $this->entityManager->close();
        }

        parent::tearDown();
    }

    public function testFullTopUpRefundUsesRefreshedClientBalance(): void
    {
        $client = $this->persistClient(1500);
        $payment = $this->persistPayment(
            $client,
            null,
            PaymentCategoryEnum::BALANCE_TOP_UP,
        );
        $clientId = $this->requireId($client->getId());

        $this->entityManager->getConnection()->executeStatement(
            'UPDATE "user" SET balance = :balance WHERE id = :id',
            ['balance' => 2000, 'id' => $clientId],
        );

        self::assertSame(1500, $client->getBalance());

        $this->service->handleSucceeded(
            $payment->getStripePaymentIntentId() ?? '',
            $payment->getAmount(),
        );
        $this->entityManager->refresh($client);

        self::assertSame(1000, $client->getBalance());
        self::assertSame(PaymentStatusEnum::REFUNDED, $payment->getStatus());
    }

    public function testFullTrainerRefundUsesRefreshedTrainerBalance(): void
    {
        $client = $this->persistClient(0);
        $trainer = $this->persistTrainer(1500);
        $payment = $this->persistPayment(
            $client,
            $trainer,
            PaymentCategoryEnum::TRAINER,
        );
        $trainerId = $this->requireId($trainer->getId());

        $this->entityManager->getConnection()->executeStatement(
            'UPDATE "user" SET balance = :balance WHERE id = :id',
            ['balance' => 2000, 'id' => $trainerId],
        );

        self::assertSame(1500, $trainer->getBalance());

        $this->service->handleSucceeded(
            $payment->getStripePaymentIntentId() ?? '',
            $payment->getAmount(),
        );
        $this->entityManager->refresh($trainer);

        self::assertSame(1000, $trainer->getBalance());
        self::assertSame(0, $trainer->getDebt());
        self::assertSame(PaymentStatusEnum::REFUNDED, $payment->getStatus());
    }

    private function persistClient(int $balance): Client
    {
        $suffix = bin2hex(random_bytes(6));
        $client = new Client();
        $client->setEmail("stripe_refresh_client_{$suffix}@example.com");
        $client->setFirstName('Stripe');
        $client->setLastName('Client');
        $client->setPhone('+37529' . random_int(1000000, 9999999));
        $client->setPassword('password');
        $client->setAge(30);
        $client->setBalance($balance);
        $client->setIsActive(true);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $clientId = $this->requireId($client->getId());
        $this->clientIds[] = $clientId;
        $this->userIds[] = $clientId;

        return $client;
    }

    private function persistTrainer(int $balance): Trainer
    {
        $suffix = bin2hex(random_bytes(6));
        $trainer = new Trainer();
        $trainer->setEmail("stripe_refresh_trainer_{$suffix}@example.com");
        $trainer->setFirstName('Stripe');
        $trainer->setLastName('Trainer');
        $trainer->setPhone('+37533' . random_int(1000000, 9999999));
        $trainer->setPassword('password');
        $trainer->setPricePerHour(1000);
        $trainer->setBalance($balance);
        $trainer->setIsActive(true);

        $this->entityManager->persist($trainer);
        $this->entityManager->flush();

        $this->userIds[] = $this->requireId($trainer->getId());

        return $trainer;
    }

    private function persistPayment(
        Client $client,
        ?Trainer $trainer,
        PaymentCategoryEnum $category,
    ): Payment {
        $payment = new Payment(PaymentMethodEnum::CARD);
        $payment->setClient($client);
        $payment->setTrainer($trainer);
        $payment->setAmount(1000);
        $payment->setCategory($category);
        $payment->setStatus(PaymentStatusEnum::REFUND_PENDING);
        $payment->setPaidAt(new DateTimeImmutable());
        $payment->setStripePaymentIntentId('pi_refresh_' . bin2hex(random_bytes(8)));

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    private function requireId(?int $id): int
    {
        self::assertIsInt($id);

        return $id;
    }
}
