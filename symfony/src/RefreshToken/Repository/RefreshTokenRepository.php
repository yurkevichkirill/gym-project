<?php

declare(strict_types=1);

namespace App\RefreshToken\Repository;

use App\RefreshToken\Entity\RefreshToken;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
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
}
