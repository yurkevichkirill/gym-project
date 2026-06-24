<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use App\Client\Entity\Client;
use App\RefreshToken\Service\RefreshTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CurrentUserFunctionalTest extends WebTestCase
{
    private KernelBrowser $browser;
    private EntityManagerInterface $entityManager;
    private RefreshTokenManager $refreshTokenManager;

    protected function setUp(): void
    {
        $this->browser = self::createClient();
        $this->browser->disableReboot();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->refreshTokenManager = self::getContainer()->get(RefreshTokenManager::class);
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

    public function testClientAccessTokenCanBeGenerated(): void
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

        $token = $this->refreshTokenManager->generateAccessToken($client);

        self::assertNotSame('', $token);
    }

    public function testUnauthenticatedRequestReturnsUnauthorized(): void
    {
        $this->browser->jsonRequest('GET', '/api/auth/me/');

        self::assertSame(
            Response::HTTP_UNAUTHORIZED,
            $this->browser->getResponse()->getStatusCode(),
            (string) $this->browser->getResponse()->getContent(),
        );
    }
}
