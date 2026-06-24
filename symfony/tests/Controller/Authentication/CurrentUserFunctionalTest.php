<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use App\Admin\Entity\Admin;
use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;
use App\User\Entity\User;
use App\User\Enum\UserRolesEnum;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenSSLAsymmetricKey;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CurrentUserFunctionalTest extends WebTestCase
{
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

    public function testClientCanGetCurrentUser(): void
    {
        $this->assertUserCanGetCurrentIdentity(
            $this->createClientUser(),
            UserRolesEnum::ROLE_CLIENT->value,
        );
    }

    public function testTrainerCanGetCurrentUser(): void
    {
        $this->assertUserCanGetCurrentIdentity(
            $this->createTrainerUser(),
            UserRolesEnum::ROLE_TRAINER->value,
        );
    }

    public function testAdminCanGetCurrentUser(): void
    {
        $this->assertUserCanGetCurrentIdentity(
            $this->createAdminUser(),
            UserRolesEnum::ROLE_ADMIN->value,
        );
    }

    public function testUnauthenticatedRequestReturnsUnauthorized(): void
    {
        $this->requestCurrentUser();

        self::assertSame(
            Response::HTTP_UNAUTHORIZED,
            $this->browser->getResponse()->getStatusCode(),
            (string) $this->browser->getResponse()->getContent(),
        );
    }

    public function testResponseContainsOnlySafeIdentityFields(): void
    {
        $client = $this->createClientUser();
        $client->setPassword('password-hash-that-must-not-leak');
        $client->setActivationToken('activation-token-that-must-not-leak');
        $this->entityManager->flush();

        $this->requestCurrentUser($this->createAccessToken($client));

        $response = $this->browser->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

        $payload = $this->decodeResponse();
        $data = $payload['data'] ?? null;
        self::assertIsArray($data);
        self::assertSame(['id', 'email', 'roles'], array_keys($data));

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringNotContainsString('password-hash-that-must-not-leak', $content);
        self::assertStringNotContainsString('activation-token-that-must-not-leak', $content);
    }

    public function testBlockedUserIsRejectedBySecurity(): void
    {
        $admin = $this->createAdminUser();
        $token = $this->createAccessToken($admin);

        $admin->setBlockedAt(new DateTimeImmutable());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->requestCurrentUser($token);

        $this->assertUnavailableUserRejected();
    }

    public function testDeletedUserIsRejectedBySecurity(): void
    {
        $client = $this->createClientUser();
        $token = $this->createAccessToken($client);

        $client->setDeletedAt(new DateTime());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->requestCurrentUser($token);

        $this->assertUnavailableUserRejected();
    }

    private function assertUserCanGetCurrentIdentity(User $user, string $expectedRole): void
    {
        $this->requestCurrentUser($this->createAccessToken($user));

        $response = $this->browser->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

        $payload = $this->decodeResponse();
        $data = $payload['data'] ?? null;
        self::assertIsArray($data);

        $id = $user->getId();
        self::assertIsInt($id);
        self::assertSame($id, $data['id'] ?? null);
        self::assertSame($user->getEmail(), $data['email'] ?? null);

        $roles = $data['roles'] ?? null;
        self::assertIsArray($roles);
        self::assertContains($expectedRole, $roles);
        self::assertContains(UserRolesEnum::ROLE_USER->value, $roles);
    }

    private function assertUnavailableUserRejected(): void
    {
        $response = $this->browser->getResponse();
        self::assertContains(
            $response->getStatusCode(),
            [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN],
            (string) $response->getContent(),
        );
    }

    private function requestCurrentUser(?string $token = null): void
    {
        $server = ['HTTP_ACCEPT' => 'application/json'];

        if ($token !== null) {
            $server['HTTP_COOKIE'] = 'access_token=' . $token;
        }

        $this->browser->request(
            'GET',
            '/api/auth/me/',
            server: $server,
        );
    }

    private function createAccessToken(User $user): string
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        self::assertIsString($projectDir);

        $privateKeyPem = file_get_contents($projectDir . '/config/jwt/private.pem');
        self::assertIsString($privateKeyPem);

        $passphrase = $_SERVER['JWT_PASSPHRASE'] ?? $_ENV['JWT_PASSPHRASE'] ?? null;
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
            'roles' => $user->getRoles(),
            'username' => $user->getUserIdentifier(),
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

    private function createClientUser(): Client
    {
        $client = new Client();
        $client->setAge(30);
        $this->persistUser($client);

        return $client;
    }

    private function createTrainerUser(): Trainer
    {
        $trainer = new Trainer();
        $trainer->setPricePerHour(1_000);
        $this->persistUser($trainer);

        return $trainer;
    }

    private function createAdminUser(): Admin
    {
        $admin = new Admin();
        $this->persistUser($admin);

        return $admin;
    }

    private function persistUser(User $user): void
    {
        $suffix = bin2hex(random_bytes(8));

        $user->setFirstName('Functional');
        $user->setLastName('User');
        $user->setEmail("current_user_{$suffix}@example.com");
        $user->setPhone('+37529' . random_int(1_000_000, 9_999_999));
        $user->setPassword('not-used-in-test');
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(): array
    {
        $content = $this->browser->getResponse()->getContent();
        self::assertIsString($content);

        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
