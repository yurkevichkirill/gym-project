<?php

declare(strict_types=1);

namespace App\RefreshToken\Repository;

use App\RefreshToken\Entity\RefreshToken;
use App\User\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
final class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function save(): void
    {
        $this->getEntityManager()->flush();
    }

    public function create(RefreshToken $refreshToken): void
    {
        $this->getEntityManager()->persist($refreshToken);
        $this->save();
    }

    public function remove(RefreshToken $refreshToken): void
    {
        $this->getEntityManager()->remove($refreshToken);
        $this->save();
    }

    public function removeAllByUser(User $user): void
    {
        $this->createQueryBuilder('rt')
            ->delete()
            ->andWhere('rt.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function revokeByTokenHash(string $tokenHash, DateTimeImmutable $revokedAt): void
    {
        $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.revokedAt', ':revokedAt')
            ->andWhere('rt.tokenHash = :tokenHash')
            ->andWhere('rt.revokedAt IS NULL')
            ->setParameter('tokenHash', $tokenHash)
            ->setParameter('revokedAt', $revokedAt)
            ->getQuery()
            ->execute();
    }

    public function revokeOldestActiveByUser(User $user, int $maxActiveTokens, DateTimeImmutable $revokedAt): void
    {
        if ($maxActiveTokens < 1) {
            $this->removeAllByUser($user);

            return;
        }

        $tokensToRevoke = $this->findBy(
            criteria: [
                'user' => $user,
                'revokedAt' => null,
            ],
            orderBy: ['id' => 'DESC'],
            limit: null,
            offset: $maxActiveTokens,
        );

        if ($tokensToRevoke === []) {
            return;
        }

        foreach ($tokensToRevoke as $refreshToken) {
            $refreshToken->setRevokedAt($revokedAt);
        }

        $this->save();
    }

    public function removeExpiredAndStaleRevoked(DateTimeImmutable $now, DateTimeImmutable $revokedBefore): int
    {
        return $this->createQueryBuilder('rt')
            ->delete()
            ->andWhere('rt.expiresAt < :now OR rt.revokedAt < :revokedBefore')
            ->setParameter('now', $now)
            ->setParameter('revokedBefore', $revokedBefore)
            ->getQuery()
            ->execute();
    }
}
