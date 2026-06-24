<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use App\Client\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use OpenSSLAsymmetricKey;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CurrentUserFunctionalTest extends WebTestCase
{
    private KernelBrowser $browser;
    private EntityManagerInterface $entityManager;
    private ?int $createdUserId = null;

    protected function setUp(): void
    {
        $this->browser = self::createClient();
        $this->browser->disableReboot();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        if ($this->createdUserId !== null) {
            $this->entityManager->getConnection()->executeStatement(
                'DELETE FROM "user" WHERE id = :userId',
                ['userId' => $this->createdUserId],
            );
        }

        $this->entityManager->close();

        parent::tearDown();
    }

    public function testCommittedClientCanGetCurrentUserWithSignedJwtCookie(): void
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

        $this->browser->request(
            'GET',
            '/api/auth/me/',
            server: [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_COOKIE' => 'access_token=' . $this->createAccessToken($client),
            ],
        );

        $response = $this->browser->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());
    }

    private function createAccessToken(Client $client): string
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        self::assertIsString($projectDir);

        $privateKeyPem = file_get_contents($projectDir . '/config/jwt/private.pem');
        self::assertIsString($privateKeyPem);

        $passphrase = getenv('JWT_PASSPHRASE');
        self::assertIsString($passphrase);

        $privateKey = openssl_pkey_get_private($privateKeyPem, $passphrase);
        self::assertInstanceOf(OpenSSLAsymmetricKey::class, $privateKey);

        $now = time();
        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'RS256',
        ], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode(json_encode([
            'iat' => $now,
            'exp' => $now + 3600,
            'roles' => $client->getRoles(),
            'username' => $client->getUserIdentifier(),
        ], JSON_THROW_ON_ERROR));
        $signingInput = $header . '.' . $payload;
        $signature = '';

        self::assertTrue(openssl_sign(
            $signingInput,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256,
        ));

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
