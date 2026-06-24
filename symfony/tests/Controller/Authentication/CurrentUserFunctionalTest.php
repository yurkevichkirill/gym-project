<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use App\Client\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CurrentUserFunctionalTest extends WebTestCase
{
    private const string PASSWORD = 'Functional-password-123';

    private KernelBrowser $browser;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->browser = self::createClient();
        $this->browser->disableReboot();
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

    public function testClientCanLoginAndReceiveAccessTokenCookie(): void
    {
        $client = new Client();
        $client->setFirstName('Functional');
        $client->setLastName('Client');
        $client->setEmail('current_client_' . bin2hex(random_bytes(8)) . '@example.com');
        $client->setPhone('+37529' . random_int(1_000_000, 9_999_999));
        $client->setIsActive(true);
        $client->setAge(30);

        $passwordHash = password_hash(self::PASSWORD, PASSWORD_BCRYPT, ['cost' => 4]);
        self::assertIsString($passwordHash);
        $client->setPassword($passwordHash);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $this->browser->request(
            'POST',
            '/api/login/',
            server: [
                'REMOTE_ADDR' => '198.51.100.10',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'email' => $client->getEmail(),
                'password' => self::PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );

        $response = $this->browser->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

        $accessTokenCookieFound = false;
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'access_token' && $cookie->getValue() !== '') {
                $accessTokenCookieFound = true;
                break;
            }
        }

        self::assertTrue($accessTokenCookieFound, 'Login response must set a non-empty access_token cookie.');
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
