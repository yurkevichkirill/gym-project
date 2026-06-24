<?php

declare(strict_types=1);

namespace App\Contact\Service;

use App\Contact\DTO\ContactRequestDTO;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class ContactMessageService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $recipientEmail,
        private string $senderEmail,
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function send(ContactRequestDTO $request): void
    {
        $name = trim($request->name);
        $emailAddress = trim($request->email);
        $message = trim($request->message);

        $email = (new Email())
            ->from($this->senderEmail)
            ->to($this->recipientEmail)
            ->replyTo($emailAddress)
            ->subject('New Evogym contact request')
            ->text(sprintf(
                "Name: %s\nEmail: %s\n\nMessage:\n%s",
                $name,
                $emailAddress,
                $message,
            ));

        $this->mailer->send($email);
    }
}
