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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CurrentUserFunctionalTest extends WebTestCase
{
    private const string PASSWORD = 'Functional-password-123';

    private static int $ipCounter = 10;

    private KernelBrowser $browser;
    private EntityManagerInterface $entityManager;
    private ?string $accessToken = null;
    private string $remoteAddress;

    protected function setUp(): void
    {
        $this->browser = self::createClient();
        $this->browser->disableReboot();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
        $this->remoteAddress = '198.51.100.' . self::$ipCounter++;
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
        $client->setActivationToken('activation-token-that-must-not-leak');
        $this->entityManager->flush();

        $passwordHash = $client->getPassword();
        self::assertIsString($passwordHash);

        $this->authenticate($client);
        $this->requestCurrentUser();

        $response = $this->browser->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

        $payload = $this->decodeResponse();
        $data = $payload['data'] ?? null;
        self::assertIsArray($data);
        self::assertSame(['id', 'email', 'roles'], array_keys($data));

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringNotContainsString($passwordHash, $content);
        self::assertStringNotContainsString('activation-token-that-must-not-leak', $content);
    }

    public function testBlockedUserIsRejectedBySecurity(): void
    {
        $admin = $this->createAdminUser();
        $this->authenticate($admin);

        $admin->setBlockedAt(new DateTimeImmutable());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->requestCurrentUser();

        $this->assertUnavailableUserRejected();
    }

    public function testDeletedUserIsRejectedBySecurity(): void
    {
        $client = $this->createClientUser();
        $this->authenticate($client);

        $client->setDeletedAt(new DateTime());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->requestCurrentUser();

        $this->assertUnavailableUserRejected();
    }

    private function assertUserCanGetCurrentIdentity(User $user, string $expectedRole): void
    {
        $this->authenticate($user);
        $this->requestCurrentUser();

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

    private function authenticate(User $user): void
    {
        $this->browser->request(
            'POST',
            '/api/login/',
            server: $this->requestServer(),
            content: json_encode([
                'email' => $user->getEmail(),
                'password' => self::PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );

        $response = $this->browser->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'access_token') {
                $this->accessToken = $cookie->getValue();
                break;
            }
        }

        self::assertNotNull($this->accessToken, 'Login response must set the access_token cookie.');
    }

    private function requestCurrentUser(): void
    {
        $this->browser->request(
            'GET',
            '/api/auth/me/',
            server: $this->requestServer(),
        );
    }

    /**
     * @return array<string, string>
     */
    private function requestServer(): array
    {
        $server = [
            'HTTP_HOST' => 'api.evogym.local',
            'HTTPS' => 'on',
            'REMOTE_ADDR' => $this->remoteAddress,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        if ($this->accessToken !== null) {
            $server['HTTP_COOKIE'] = 'access_token=' . $this->accessToken;
        }

        return $server;
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
        $passwordHash = password_hash(self::PASSWORD, PASSWORD_BCRYPT, ['cost' => 4]);
        self::assertIsString($passwordHash);

        $user->setFirstName('Functional');
        $user->setLastName('User');
        $user->setEmail("current_user_{$suffix}@example.com");
        $user->setPhone('+37529' . random_int(1_000_000, 9_999_999));
        $user->setIsActive(true);
        $user->setPassword($passwordHash);

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
