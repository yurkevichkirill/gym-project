<?php

namespace App\Booking\Repository;

use App\Booking\Entity\Booking;
use App\Client\Entity\Client;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function save(): void
    {
        $this->getEntityManager()->flush();
    }

    public function create(Booking $booking): void
    {
        $this->getEntityManager()->persist($booking);
        $this->save();
    }

    public function remove(Booking $booking): void
    {
        $this->getEntityManager()->remove($booking);
        $this->save();
    }

    public function getClientBookingsByDate(Client $client, DateTimeImmutable $date): array
    {
        return  $this->createQueryBuilder('b')
            ->innerJoin("b.training", "t")
            ->innerJoin("t.trainerWorkTime", "wt")
            ->where("wt.date = :date")
            ->setParameter("date", $date)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Booking[] Returns an array of Booking objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Booking
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
