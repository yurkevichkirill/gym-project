<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Client\Entity\Client;
use App\Payment\Exception\InsufficientFundsException;
use App\Trainer\Entity\Trainer;
use InvalidArgumentException;

final readonly class BalanceService
{
    public function chargeTrainer(Trainer $trainer, int $amount): void
    {
        $this->assertPositiveAmount($amount);
        $trainer->setBalance($trainer->getBalance() - $amount);
    }

    public function depositTrainer(Trainer $trainer, int $amount): void
    {
        $this->assertPositiveAmount($amount);
        $trainer->setBalance($trainer->getBalance() + $amount);
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
