<?php

declare(strict_types=1);

namespace App\Booking\Query;

use App\Booking\DTO\BookingFilter;
use App\Booking\DTO\GetBookings;
use App\Booking\Repository\BookingRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class BookingsQuery
{
    private const array SORT_MAP = [
        'trainingId' => 't.id',
        'date' => 'w.date',
        'startTime' => 't.startTime',
        'durationMinutes' => 't.durationMinutes',
    ];

    public function __construct(
        private BookingRepository      $bookingRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetBookings $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($dto): array {
            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $alias => $order) {
                $field = self::SORT_MAP[$alias] ?? "b.$alias";
                $qb->addOrderBy($field, $order);
            }

            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            if ($dto->filter->client) {
                $item->tag(['bookings_list_' . $dto->filter->client->getId()]);
            } else {
                $item->tag(['bookings_list_all']);
            }

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(BookingFilter $filter): int
    {
        return (int) $this->createQuery($filter)
            ->select("COUNT(b.id)")
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQuery(BookingFilter $filter, bool $isCount = false): QueryBuilder
    {
        $qb = $this->bookingRepo->createQueryBuilder('b')
            ->innerJoin("b.payment", 'p')
            ->addSelect('p')
            ->innerJoin("p.trainer", "trainer")
            ->addSelect("trainer")
            ->innerJoin("trainer.trainingType", 'type')
            ->addSelect("type");

        if (!$isCount) {
            $qb->addSelect('t', 'w');
        }

        $qb->innerJoin('b.training', 't')
            ->innerJoin('t.trainerWorkTime', 'w');

        if ($filter->client) {
            $qb->andWhere('b.client = :client')
                ->setParameter('client', $filter->client);
        }

        if ($filter->trainer) {
            $qb->andWhere('trainer = :trainer')
                ->setParameter('trainer', $filter->trainer);
        }

        if ($filter->status) {
            $qb->andWhere('b.status = :status')
                ->setParameter('status', $filter->status);
        }

        if ($filter->date) {
            $qb->andWhere('w.date = :date')
                ->setParameter('date', $filter->date);
        }

        if ($filter->startTime) {
            $qb->andWhere('t.startTime = :startTime')
                ->setParameter('startTime', $filter->startTime);
        }

        if ($filter->durationMinutes !== null) {
            $qb->andWhere('t.durationMinutes = :durationMinutes')
                ->setParameter('durationMinutes', $filter->durationMinutes);
        }

        return $qb;
    }

    private function generateCacheKey(GetBookings $query): string
    {
        $f = $query->filter;

        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
            'clientId' => $f->client?->getId(),
            'trainerId' => $f->trainer?->getId(),
            'status' => $f->status,
            'date' => $f->date?->format('Y-m-d'),
            'startTime' => $f->startTime?->format('H:i:s'),
            'durationMinutes' => $f->durationMinutes,
        ];

        return 'bookings_' . md5(json_encode($params));
    }
}
