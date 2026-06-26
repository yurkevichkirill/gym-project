<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Client\Entity\Client;
use App\Payment\Exception\InsufficientFundsException;
use App\Trainer\Entity\Trainer;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final readonly class BalanceService
{
    public function __construct(
        private LoggerInterface $paymentLogger,
    ) {}

    public function chargeTrainer(Trainer $trainer, int $amount): void
    {
        $this->assertPositiveAmount($amount);

        $recoveredFromBalance = min($trainer->getBalance(), $amount);
        $debtIncrease = $amount - $recoveredFromBalance;

        $trainer->setBalance($trainer->getBalance() - $recoveredFromBalance);

        if ($debtIncrease === 0) {
            return;
        }

        $trainer->setDebt($trainer->getDebt() + $debtIncrease);

        $this->paymentLogger->critical('trainer.debt.created', [
            'trainer_id' => $trainer->getId(),
            'charged_amount' => $amount,
            'recovered_from_balance' => $recoveredFromBalance,
            'debt_increase' => $debtIncrease,
            'debt' => $trainer->getDebt(),
            'balance' => $trainer->getBalance(),
            'action' => 'trainer_debt_recovery_required',
        ]);
    }

    public function depositTrainer(Trainer $trainer, int $amount): void
    {
        $this->assertPositiveAmount($amount);

        $repaidDebt = min($trainer->getDebt(), $amount);
        if ($repaidDebt > 0) {
            $trainer->setDebt($trainer->getDebt() - $repaidDebt);
        }

        $remainingAmount = $amount - $repaidDebt;
        if ($remainingAmount > 0) {
            $trainer->setBalance($trainer->getBalance() + $remainingAmount);
        }
    }

    public function chargeClient(Client $client, int $amount): void
    {
        $this->assertPositiveAmount($amount);

        $newBalance = $client->getBalance() - $amount;

        if ($newBalance < 0) {
            throw new InsufficientFundsException('Insufficient funds on client balance.');
        }

        $client->setBalance($newBalance);
    }

    public function reverseClientCredit(Client $client, int $amount): void
    {
        $this->assertPositiveAmount($amount);

        $newBalance = $client->getBalance() - $amount;
        $client->setBalance($newBalance);

        if ($newBalance < 0) {
            $this->paymentLogger->critical('client.balance.negative_after_credit_reversal', [
                'client_id' => $client->getId(),
                'reversed_amount' => $amount,
                'balance' => $newBalance,
                'action' => 'client_debt_recovery_required',
            ]);
        }
    }

    public function depositClient(Client $client, int $amount): void
    {
        $this->assertPositiveAmount($amount);
        $client->setBalance($client->getBalance() + $amount);
    }

    private function assertPositiveAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }
    }
}
