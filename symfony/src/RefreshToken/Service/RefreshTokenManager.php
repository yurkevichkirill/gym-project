<?php

declare(strict_types=1);

namespace App\RefreshToken\Service;

use App\RefreshToken\Entity\RefreshToken;
use App\RefreshToken\Repository\RefreshTokenRepository;
use App\User\Entity\User;
use DateTimeImmutable;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Random\RandomException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class RefreshTokenManager
{
    private const string TOKEN_HASH_ALGORITHM = 'sha256';

    public function __construct(
        private RefreshTokenRepository $repo,
        private JWTTokenManagerInterface $jwtManager,
    )
    {}

    public function create(string $refreshToken, User $user): void
    {
        $entityRefreshToken = new RefreshToken();
        $entityRefreshToken->setTokenHash(
            $this->hashToken($refreshToken)
        );
        $entityRefreshToken->setUser($user);
        $entityRefreshToken->setExpiresAt(new DateTimeImmutable('+7 days'));

        $this->repo->create($entityRefreshToken);
    }

    /**
     * @throws RandomException
     * @throws UnauthorizedHttpException
     * @throws AccessDeniedHttpException
     *
     * @return array{0: string, 1: string}
     */
    public function refresh(?string $refreshToken): array
    {
        if ($refreshToken === null || $refreshToken === '') {
            throw new UnauthorizedHttpException('Bearer', 'No refresh token');
        }

        $tokenEntity = $this->repo->findOneBy([
            'tokenHash' => $this->hashToken($refreshToken),
        ]);

        if ($tokenEntity === null || $tokenEntity->getExpiresAt() < new DateTimeImmutable()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid refresh token');
        }

        $user = $tokenEntity->getUser();

        if (
            $user->getDeletedAt() !== null
            || $user->getBlockedAt() !== null
            || !$user->isActive()
        ) {
            $this->repo->removeAllByUser($user);
            throw new AccessDeniedHttpException('User is unavailable');
        }

        $this->repo->remove($tokenEntity);

        $newRefreshToken = $this->generateRefreshToken();
        $this->create($newRefreshToken, $user);

        $newAccessToken = $this->jwtManager->create($user);

        return [$newAccessToken, $newRefreshToken];
    }

    /**
     * @throws RandomException
     */
    public function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(64));
    }

    /**
     * @throws AccessDeniedHttpException
     */
    public function generateAccessToken(User $user): string
    {
        if ($user->getDeletedAt() !== null) {
            throw new AccessDeniedHttpException('User is deleted');
        }

        return $this->jwtManager->create($user);
    }

    public function revoke(?string $refreshToken): void
    {
        if ($refreshToken === null || $refreshToken === '') {
            return;
        }

        $this->repo->removeByTokenHash(
            $this->hashToken($refreshToken)
        );
    }

    private function hashToken(string $refreshToken): string
    {
        return hash(self::TOKEN_HASH_ALGORITHM, $refreshToken);
    }
}
