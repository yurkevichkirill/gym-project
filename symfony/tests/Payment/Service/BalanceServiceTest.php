<?php

declare(strict_types=1);

namespace App\Tests\Payment\Service;

use App\Payment\Service\BalanceService;
use App\Trainer\Entity\Trainer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

final class BalanceServiceTest extends TestCase
{
    public function testChargeTrainerMovesUncoveredAmountToDebt(): void
    {
        $trainer = new Trainer();
        $trainer->setBalance(300);
        $trainer->setDebt(200);
        $logger = new InMemoryBalanceLogger();
        $service = new BalanceService($logger);

        $service->chargeTrainer($trainer, 1000);

        self::assertSame(0, $trainer->getBalance());
        self::assertSame(900, $trainer->getDebt());
        self::assertCount(1, $logger->records);
        self::assertSame('critical', $logger->records[0]['level']);
        self::assertSame('trainer.debt.created', $logger->records[0]['message']);
        self::assertSame(700, $logger->records[0]['context']['debt_increase'] ?? null);
        self::assertSame(900, $logger->records[0]['context']['debt'] ?? null);
    }

    public function testChargeTrainerUsesAvailableBalanceWithoutCreatingDebt(): void
    {
        $trainer = new Trainer();
        $trainer->setBalance(1000);
        $logger = new InMemoryBalanceLogger();
        $service = new BalanceService($logger);

        $service->chargeTrainer($trainer, 400);

        self::assertSame(600, $trainer->getBalance());
        self::assertSame(0, $trainer->getDebt());
        self::assertSame([], $logger->records);
    }

    public function testDepositTrainerRepaysDebtBeforeIncreasingBalance(): void
    {
        $trainer = new Trainer();
        $trainer->setBalance(100);
        $trainer->setDebt(700);
        $service = new BalanceService(new InMemoryBalanceLogger());

        $service->depositTrainer($trainer, 500);

        self::assertSame(100, $trainer->getBalance());
        self::assertSame(200, $trainer->getDebt());

        $service->depositTrainer($trainer, 300);

        self::assertSame(200, $trainer->getBalance());
        self::assertSame(0, $trainer->getDebt());
    }

    public function testTrainerRejectsNegativeBalance(): void
    {
        $trainer = new Trainer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Trainer balance cannot be negative.');

        $trainer->setBalance(-1);
    }

    public function testTrainerRejectsNegativeDebt(): void
    {
        $trainer = new Trainer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Trainer debt cannot be negative.');

        $trainer->setDebt(-1);
    }
}

final class InMemoryBalanceLogger extends AbstractLogger
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
