<?php

declare(strict_types=1);

namespace App\Tests\Contact\Service;

use App\Contact\DTO\ContactRequestDTO;
use App\Contact\Service\ContactMessageService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class ContactMessageServiceTest extends TestCase
{
    public function testSendUsesConfiguredAddressesAndPlainTextBody(): void
    {
        $mailer = new RecordingContactMailer();
        $service = new ContactMessageService(
            mailer: $mailer,
            recipientEmail: 'contact@evogym.local',
            senderEmail: 'noreply@evogym.local',
        );

        $service->send(new ContactRequestDTO(
            name: ' Alice Example ',
            email: ' alice@example.com ',
            message: ' Please tell me about personal training. ',
        ));

        self::assertInstanceOf(Email::class, $mailer->message);
        $email = $mailer->message;

        self::assertSame('noreply@evogym.local', $email->getFrom()[0]->getAddress());
        self::assertSame('contact@evogym.local', $email->getTo()[0]->getAddress());
        self::assertSame('alice@example.com', $email->getReplyTo()[0]->getAddress());
        self::assertSame('New Evogym contact request', $email->getSubject());
        self::assertSame(
            "Name: Alice Example\nEmail: alice@example.com\n\nMessage:\nPlease tell me about personal training.",
            $email->getTextBody(),
        );
        self::assertNull($email->getHtmlBody());
    }
}

final class RecordingContactMailer implements MailerInterface
{
    public ?RawMessage $message = null;

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        $this->message = $message;
    }
}
