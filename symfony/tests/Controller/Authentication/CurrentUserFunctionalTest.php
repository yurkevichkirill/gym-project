<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use App\Client\Entity\Client;
use App\RefreshToken\Service\RefreshTokenManager;
use App\User\Enum\UserRolesEnum;
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

    public function testClientCanGetCurrentUser(): void
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
        $this->browser->request(
            'GET',
            '/api/auth/me/',
            server: [
                'HTTP_COOKIE' => 'access_token=' . $token,
                'HTTP_ACCEPT' => 'application/json',
            ],
        );

        $response = $this->browser->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

        $content = $response->getContent();
        self::assertIsString($content);
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        $data = $payload['data'] ?? null;
        self::assertIsArray($data);
        self::assertSame($client->getId(), $data['id'] ?? null);
        self::assertSame($client->getEmail(), $data['email'] ?? null);

        $roles = $data['roles'] ?? null;
        self::assertIsArray($roles);
        self::assertContains(UserRolesEnum::ROLE_CLIENT->value, $roles);
        self::assertContains(UserRolesEnum::ROLE_USER->value, $roles);
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
