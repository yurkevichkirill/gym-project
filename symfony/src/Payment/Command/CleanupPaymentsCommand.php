<?php

declare(strict_types=1);

namespace App\Payment\Command;

use App\Payment\Service\PaymentSettlementService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Throwable;

#[AsCommand(name: 'app:payments:cleanup')]
readonly class CleanupPaymentsCommand
{
    public function __construct(
        private PaymentSettlementService $paymentSettlementService,
    )
    {}

    /**
     * @throws Throwable
     */
    public function __invoke(): int
    {
        $this->paymentSettlementService->cancelExpiredPayments();

        return Command::SUCCESS;
    }
}
