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
    public function __construct(
        private RefreshTokenRepository $repo,
        private JWTTokenManagerInterface $jwtManager,
    )
    {}

    public function create(string $refreshToken, User $user): void
    {
        $entityRefreshToken = new RefreshToken();
        $entityRefreshToken->setToken($refreshToken);
        $entityRefreshToken->setUser($user);
        $entityRefreshToken->setExpiresAt(new DateTimeImmutable('+7 days'));

        $this->repo->create($entityRefreshToken);
    }

    /**
     * @throws RandomException
     * @throws UnauthorizedHttpException
     * @throws AccessDeniedHttpException
     */
    public function refresh(?string $refreshToken): array
    {
        if (!$refreshToken) {
            throw new UnauthorizedHttpException('Bearer', 'No refresh token');
        }

        $tokenEntity = $this->repo->findOneBy(['token' => $refreshToken]);

        if (!$tokenEntity || $tokenEntity->getExpiresAt() < new DateTimeImmutable()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid refresh token');
        }

        $user = $tokenEntity->getUser();

        if ($user->isDeleted()) {
            $this->repo->removeAllByUser($user);
            throw new AccessDeniedHttpException('User is blocked');
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
        if ($user->getDeletedAt()) {
            throw new AccessDeniedHttpException('User is deleted');
        }

        return $this->jwtManager->create($user);
    }
}
