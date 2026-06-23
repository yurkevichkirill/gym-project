<?php

declare(strict_types=1);

namespace App\Tests\ImportJob\MessageHandler;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\ImportJob\DTO\CreateClientImport;
use App\ImportJob\Entity\ImportJob;
use App\ImportJob\Message\ImportMessage;
use App\ImportJob\Message\SendActivationEmailMessage;
use App\ImportJob\MessageHandler\ImportHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class ImportHandlerTest extends KernelTestCase
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

    public function testImportedClientIsDispatchedForActivationAfterIdentityIsGenerated(): void
    {
        $transport = self::getContainer()->get('messenger.transport.client_outbox');
        self::assertInstanceOf(InMemoryTransport::class, $transport);
        $transport->reset();

        $job = new ImportJob(1);
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $jobId = $job->getId();
        self::assertNotNull($jobId);

        $email = sprintf('import-%s@example.com', bin2hex(random_bytes(8)));
        $phone = sprintf('+37529%07d', random_int(0, 9_999_999));

        $handler = self::getContainer()->get(ImportHandler::class);
        self::assertInstanceOf(ImportHandler::class, $handler);

        $handler(new ImportMessage(
            new CreateClientImport(
                email: $email,
                firstName: 'Imported',
                lastName: 'Client',
                phone: $phone,
                age: 25,
            ),
            $jobId,
            1,
        ));

        $clientRepository = self::getContainer()->get(ClientRepository::class);
        self::assertInstanceOf(ClientRepository::class, $clientRepository);

        $client = $clientRepository->findOneBy(['email' => $email]);
        self::assertInstanceOf(Client::class, $client);
        self::assertNotNull($client->getId());

        $activationMessages = [];
        foreach ($transport->getSent() as $envelope) {
            $message = $envelope->getMessage();
            if ($message instanceof SendActivationEmailMessage) {
                $activationMessages[] = $message;
            }
        }

        self::assertCount(1, $activationMessages);
        self::assertSame($client->getId(), $activationMessages[0]->clientId);
    }
}
