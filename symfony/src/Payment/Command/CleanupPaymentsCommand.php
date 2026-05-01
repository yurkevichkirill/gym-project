<?php

declare(strict_types=1);

namespace App\Payment\Command;

use App\Payment\Service\PaymentService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Throwable;

#[AsCommand(name: 'app:payments:cleanup')]
readonly class CleanupPaymentsCommand
{
    public function __construct(
        private PaymentService $paymentService,
    )
    {}

    /**
     * @throws Throwable
     */
    public function __invoke(): int
    {
        $this->paymentService->cancelExpiredPayments();

        return Command::SUCCESS;
    }
}
