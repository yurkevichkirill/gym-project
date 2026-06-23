<?php

declare(strict_types=1);

namespace App\Tests\Cache;

use App\Cache\Message\InvalidateCacheMessage;
use App\Client\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class CacheInvalidatorListenerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private InMemoryTransport $cacheOutbox;
    private ?int $clientId = null;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $transport = $container->get('messenger.transport.cache_outbox');
        self::assertInstanceOf(InMemoryTransport::class, $transport);
        $this->cacheOutbox = $transport;
        $this->cacheOutbox->reset();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            if ($this->clientId !== null) {
                $this->entityManager->getConnection()->executeStatement(
                    'DELETE FROM "user" WHERE id = :id',
                    ['id' => $this->clientId],
                );
            }

            $this->entityManager->close();
        }

        parent::tearDown();
    }

    public function testFlushQueuesCacheInvalidationInsteadOfExecutingItInline(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $client = new Client();
        $client->setEmail("cache_outbox_{$suffix}@example.com");
        $client->setFirstName('Cache');
        $client->setLastName('Outbox');
        $client->setPhone('+37529' . random_int(1000000, 9999999));
        $client->setPassword('password');
        $client->setAge(30);
        $client->setIsActive(true);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $clientId = $client->getId();
        self::assertIsInt($clientId);
        $this->clientId = $clientId;

        $sent = $this->cacheOutbox->getSent();
        self::assertCount(1, $sent);

        $message = $sent[0]->getMessage();
        self::assertInstanceOf(InvalidateCacheMessage::class, $message);
        self::assertSame(['clients_list'], $message->tags);
        self::assertSame([], $message->groups);
    }
}
