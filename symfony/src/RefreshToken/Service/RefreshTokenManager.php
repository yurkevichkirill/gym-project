<?php

declare(strict_types=1);

namespace App\RefreshToken\Service;

use App\RefreshToken\Entity\RefreshToken;
use App\RefreshToken\Repository\RefreshTokenRepository;
use App\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use LogicException;
use Random\RandomException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class RefreshTokenManager
{
    private const string TOKEN_HASH_ALGORITHM = 'sha256';
    private const int MAX_ACTIVE_REFRESH_TOKENS = 5;
    private const string ROTATION_SUCCESS = 'success';
    private const string ROTATION_INVALID = 'invalid';
    private const string ROTATION_REUSE = 'reuse';
    private const string ROTATION_USER_UNAVAILABLE = 'user_unavailable';

    public function __construct(
        private RefreshTokenRepository $repo,
        private JWTTokenManagerInterface $jwtManager,
        private EntityManagerInterface $entityManager,
    ) {}

    public function create(string $refreshToken, User $user): void
    {
        $entityRefreshToken = new RefreshToken();
        $entityRefreshToken->setTokenHash(
            $this->hashToken($refreshToken)
        );
        $entityRefreshToken->setUser($user);
        $entityRefreshToken->setExpiresAt(new DateTimeImmutable('+7 days'));

        $this->repo->create($entityRefreshToken);
        $this->repo->revokeOldestActiveByUser($user, self::MAX_ACTIVE_REFRESH_TOKENS, new DateTimeImmutable());
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

        /** @var array{status: string, accessToken: ?string, refreshToken: ?string} $rotation */
        $rotation = $this->entityManager->wrapInTransaction(
            fn (): array => $this->rotate($this->hashToken($refreshToken)),
        );

        if ($rotation['status'] === self::ROTATION_SUCCESS) {
            if ($rotation['accessToken'] === null || $rotation['refreshToken'] === null) {
                throw new LogicException('Successful refresh token rotation must return both tokens');
            }

            return [$rotation['accessToken'], $rotation['refreshToken']];
        }

        if ($rotation['status'] === self::ROTATION_REUSE) {
            throw new UnauthorizedHttpException('Bearer', 'Refresh token reuse detected');
        }

        if ($rotation['status'] === self::ROTATION_USER_UNAVAILABLE) {
            throw new AccessDeniedHttpException('User is unavailable');
        }

        throw new UnauthorizedHttpException('Bearer', 'Invalid refresh token');
    }

    /**
     * @throws RandomException
     *
     * @return array{status: string, accessToken: ?string, refreshToken: ?string}
     */
    private function rotate(string $tokenHash): array
    {
        $tokenEntity = $this->repo->findOneByTokenHashForUpdate($tokenHash);

        if ($tokenEntity === null) {
            return $this->rotationResult(self::ROTATION_INVALID);
        }

        $user = $tokenEntity->getUser();

        if ($tokenEntity->getRevokedAt() !== null) {
            $this->repo->removeAllByUser($user);

            return $this->rotationResult(self::ROTATION_REUSE);
        }

        $now = new DateTimeImmutable();

        if ($tokenEntity->getExpiresAt() < $now) {
            $this->repo->remove($tokenEntity);

            return $this->rotationResult(self::ROTATION_INVALID);
        }

        if (
            $user->getDeletedAt() !== null
            || $user->getBlockedAt() !== null
            || !$user->isActive()
        ) {
            $this->repo->removeAllByUser($user);

            return $this->rotationResult(self::ROTATION_USER_UNAVAILABLE);
        }

        $tokenEntity->setRevokedAt($now);
        $this->repo->save();

        $newRefreshToken = $this->generateRefreshToken();
        $this->create($newRefreshToken, $user);

        return $this->rotationResult(
            status: self::ROTATION_SUCCESS,
            accessToken: $this->jwtManager->create($user),
            refreshToken: $newRefreshToken,
        );
    }

    /**
     * @return array{status: string, accessToken: ?string, refreshToken: ?string}
     */
    private function rotationResult(
        string $status,
        ?string $accessToken = null,
        ?string $refreshToken = null,
    ): array {
        return [
            'status' => $status,
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ];
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

        $this->repo->revokeByTokenHash(
            $this->hashToken($refreshToken),
            new DateTimeImmutable(),
        );
    }

    private function hashToken(string $refreshToken): string
    {
        return hash(self::TOKEN_HASH_ALGORITHM, $refreshToken);
    }
}
