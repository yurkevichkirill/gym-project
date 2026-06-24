<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use App\Client\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CurrentUserFunctionalTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private ?int $createdUserId = null;

    protected function setUp(): void
    {
        self::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        if ($this->createdUserId !== null) {
            $connection = $this->entityManager->getConnection();
            $connection->executeStatement(
                'DELETE FROM refresh_token WHERE user_id = :userId',
                ['userId' => $this->createdUserId],
            );
            $connection->executeStatement(
                'DELETE FROM "user" WHERE id = :userId',
                ['userId' => $this->createdUserId],
            );
        }

        $this->entityManager->close();

        parent::tearDown();
    }

    public function testCommittedClientFixtureCanBeCleanedUp(): void
    {
        $client = new Client();
        $client->setFirstName('Functional');
        $client->setLastName('Client');
        $client->setEmail('current_client_' . bin2hex(random_bytes(8)) . '@example.com');
        $client->setPhone('+37529' . random_int(1_000_000, 9_999_999));
        $client->setPassword('not-used-in-test');
        $client->setIsActive(true);
        $client->setAge(30);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $userId = $client->getId();
        self::assertIsInt($userId);
        $this->createdUserId = $userId;
    }
}
