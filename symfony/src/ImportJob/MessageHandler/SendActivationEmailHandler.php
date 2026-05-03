<?php

declare(strict_types=1);

namespace App\ImportJob\MessageHandler;

use App\ImportJob\Message\SendActivationEmailMessage;
use App\ImportJob\Service\ActivationEmailService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendActivationEmailHandler
{
    public function __construct(
        private ActivationEmailService $emailService,
    )
    {}

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendActivationEmailMessage $message): void
    {
        $this->emailService->send($message);
    }
}
