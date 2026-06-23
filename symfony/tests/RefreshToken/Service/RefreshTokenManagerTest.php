<?php

declare(strict_types=1);

namespace App\Tests\RefreshToken\Service;

use App\Manager\Entity\Manager;
use App\RefreshToken\Repository\RefreshTokenRepository;
use App\RefreshToken\Service\RefreshTokenManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class RefreshTokenManagerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private RefreshTokenRepository $refreshTokenRepository;
    private RefreshTokenManager $refreshTokenManager;
    private ?int $userId = null;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->refreshTokenRepository = $container->get(RefreshTokenRepository::class);
        $this->refreshTokenManager = $container->get(RefreshTokenManager::class);
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager) && $this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->getConnection()->rollBack();
        }

        if (isset($this->entityManager) && $this->userId !== null) {
            $connection = $this->entityManager->getConnection();
            $connection->executeStatement(
                'DELETE FROM refresh_token WHERE user_id = :userId',
                ['userId' => $this->userId],
            );
            $connection->executeStatement(
                'DELETE FROM "user" WHERE id = :userId',
                ['userId' => $this->userId],
            );
        }

        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }

        parent::tearDown();
    }

    public function testCreateStoresOnlyHashesAndLimitsActiveSessions(): void
    {
        $user = $this->persistManager();
        $firstToken = bin2hex(random_bytes(64));
        $this->refreshTokenManager->create($firstToken, $user);

        for ($index = 1; $index < 6; ++$index) {
            $this->refreshTokenManager->create(bin2hex(random_bytes(64)), $user);
        }

        self::assertNull($this->refreshTokenRepository->findOneBy(['tokenHash' => $firstToken]));
        self::assertNotNull($this->refreshTokenRepository->findOneBy([
            'tokenHash' => hash('sha256', $firstToken),
        ]));
        self::assertSame(6, $this->refreshTokenRepository->count(['user' => $user]));
        self::assertSame(5, $this->refreshTokenRepository->count([
            'user' => $user,
            'revokedAt' => null,
        ]));
    }

    public function testRefreshRotatesTokenAndReuseRevokesEverySession(): void
    {
        $user = $this->persistManager();
        $refreshToken = bin2hex(random_bytes(64));
        $this->refreshTokenManager->create($refreshToken, $user);

        [$accessToken, $rotatedRefreshToken] = $this->refreshTokenManager->refresh($refreshToken);

        self::assertNotSame('', $accessToken);
        self::assertNotSame($refreshToken, $rotatedRefreshToken);

        $previousToken = $this->refreshTokenRepository->findOneBy([
            'tokenHash' => hash('sha256', $refreshToken),
        ]);
        $activeToken = $this->refreshTokenRepository->findOneBy([
            'tokenHash' => hash('sha256', $rotatedRefreshToken),
        ]);

        self::assertNotNull($previousToken);
        self::assertNotNull($previousToken->getRevokedAt());
        self::assertNotNull($activeToken);
        self::assertNull($activeToken->getRevokedAt());

        $this->refreshTokenManager->create(bin2hex(random_bytes(64)), $user);

        try {
            $this->refreshTokenManager->refresh($refreshToken);
            self::fail('Expected refresh token reuse to be rejected.');
        } catch (UnauthorizedHttpException $exception) {
            self::assertSame('Refresh token reuse detected', $exception->getMessage());
        }

        self::assertSame(0, $this->refreshTokenRepository->count(['user' => $user]));
    }

    public function testRefreshRejectsBlockedUserAndRevokesSessions(): void
    {
        $user = $this->persistManager();
        $refreshToken = bin2hex(random_bytes(64));
        $this->refreshTokenManager->create($refreshToken, $user);

        $user->setBlockedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        try {
            $this->refreshTokenManager->refresh($refreshToken);
            self::fail('Expected blocked user refresh to be rejected.');
        } catch (AccessDeniedHttpException $exception) {
            self::assertSame('User is unavailable', $exception->getMessage());
        }

        self::assertSame(0, $this->refreshTokenRepository->count(['user' => $user]));
    }

    private function persistManager(): Manager
    {
        $suffix = bin2hex(random_bytes(6));
        $manager = new Manager();
        $manager->setEmail("refresh_manager_{$suffix}@example.com");
        $manager->setFirstName('Refresh');
        $manager->setLastName('Manager');
        $manager->setPhone('+37529' . random_int(1000000, 9999999));
        $manager->setPassword('password');
        $manager->setIsActive(true);

        $this->entityManager->persist($manager);
        $this->entityManager->flush();

        $managerId = $manager->getId();
        self::assertIsInt($managerId);
        $this->userId = $managerId;

        return $manager;
    }
}
