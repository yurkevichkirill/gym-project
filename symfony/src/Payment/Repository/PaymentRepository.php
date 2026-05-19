<?php

declare(strict_types=1);

namespace App\Payment\Repository;

use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentStatusEnum;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
final class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function create(Payment $payment): void
    {
        $this->getEntityManager()->persist($payment);
    }

    public function remove(Payment $payment): void
    {
        $this->getEntityManager()->remove($payment);
    }

    public function findOneByStripePaymentIntentId(string $intentId): ?Payment
    {
        return $this->findOneBy([
            'stripePaymentIntentId' => $intentId,
        ]);
    }

    public function findExpiredPending(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.expiresAt IS NOT NULL')
            ->andWhere('p.expiresAt < :now')
            ->setParameter('status', PaymentStatusEnum::PENDING)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }
}
