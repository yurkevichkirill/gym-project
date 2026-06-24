<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use App\Client\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CurrentUserFunctionalTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private JWTTokenManagerInterface $jwtManager;

    protected function setUp(): void
    {
        self::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $jwtManager = self::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        self::assertInstanceOf(JWTTokenManagerInterface::class, $jwtManager);
        $this->jwtManager = $jwtManager;

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

    public function testJwtCanBeGeneratedForPersistedClient(): void
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

        $token = $this->jwtManager->create($client);

        self::assertNotSame('', $token);
    }
}
