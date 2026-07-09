<?php

declare(strict_types=1);

namespace App\Tests\ImportJob\Service;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\ImportJob\Message\SendActivationEmailMessage;
use App\ImportJob\Service\ActivationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

#[CoversClass(ActivationEmailService::class)]
final class ActivationEmailServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->getConnection()->rollBack();
        }

        $this->entityManager->close();

        parent::tearDown();
    }

    public function testSendUsesConfiguredSenderAddress(): void
    {
        $client = new Client();
        $client->setEmail(sprintf('activation-%s@example.com', bin2hex(random_bytes(8))));
        $client->setFirstName('Imported');
        $client->setLastName('Client');
        $client->setPhone(sprintf('+37529%07d', random_int(0, 9_999_999)));
        $client->setAge(25);
        $client->setActivationToken('token/with+characters');

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $clientId = $client->getId();
        self::assertNotNull($clientId);

        $clientRepository = self::getContainer()->get(ClientRepository::class);
        self::assertInstanceOf(ClientRepository::class, $clientRepository);

        $mailer = new RecordingActivationMailer();
        $service = new ActivationEmailService(
            clientRepo: $clientRepository,
            mailer: $mailer,
            clientActivationUrl: 'https://example.com/activate/',
            senderEmail: 'activation@example.com',
        );

        $service->send(new SendActivationEmailMessage($clientId));

        self::assertInstanceOf(TemplatedEmail::class, $mailer->message);
        self::assertSame('activation@example.com', $mailer->message->getFrom()[0]->getAddress());
        self::assertSame($client->getEmail(), $mailer->message->getTo()[0]->getAddress());
        self::assertSame(
            "Hello Imported,\n\nActivate your account:\nhttps://example.com/activate/?token=token%2Fwith%2Bcharacters\n",
            $mailer->message->getTextBody(),
        );
    }
}

final class RecordingActivationMailer implements MailerInterface
{
    public ?RawMessage $message = null;

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        $this->message = $message;
    }
}
