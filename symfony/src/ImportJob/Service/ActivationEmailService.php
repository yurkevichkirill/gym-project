<?php

declare(strict_types=1);

namespace App\ImportJob\Service;

use App\Client\Repository\ClientRepository;
use App\ImportJob\Message\SendActivationEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

final readonly class ActivationEmailService
{
    public function __construct(
        private ClientRepository $clientRepo,
        private MailerInterface $mailer,
        private string $clientActivationUrl,
    )
    {}

    /**
     * @throws TransportExceptionInterface
     */
    public function send(SendActivationEmailMessage $message): void
    {
        $client = $this->clientRepo->find($message->clientId);

        if ($client === null || $client->getActivationToken() === null) {
            return;
        }
        $activationLink = sprintf(
            '%s?token=%s',
            rtrim($this->clientActivationUrl, '?&'),
            rawurlencode($client->getActivationToken()),
        );

        $email = new TemplatedEmail()
            ->from('noreply@evogym.local')
            ->to($client->getEmail())
            ->subject('Activate your account')
            ->text(
                sprintf(
                    "Hello %s,\n\nActivate your account:\n%s.",
                    $client->getFirstName(),
                    $activationLink
                )
            );

        $this->mailer->send($email);
    }
}
