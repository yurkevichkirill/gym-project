<?php

declare(strict_types=1);

namespace App\Booking\Query;

use App\Booking\DTO\GetClientBookings;
use App\Booking\Repository\BookingRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class ClientBookingsQuery
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
    public function handle(GetClientBookings $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($dto): array
        {
            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $alias => $order) {
                $field = self::SORT_MAP[$alias] ?? "b.$alias";
                $qb->addOrderBy("$field", $order);
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $item->tag(['bookings_list']);

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(array $filter): int
    {
        return $this->createQuery($filter, true)->select("COUNT(b.id)")->getQuery()->getSingleScalarResult();
    }

    private function createQuery(array $filter, bool $isCount = false): QueryBuilder
    {
        $qb = $this->bookingRepo->createQueryBuilder('b');

        if (!$isCount) {
            $qb->addSelect('t', 'w');
        }

        $qb->innerJoin('b.training', 't')
            ->innerJoin('t.trainerWorkTime', 'w')
            ->andWhere('b.client = :client')
            ->setParameter('client', $filter['client']);

        if(isset($filter['trainer'])) {
            $qb->andWhere('w.trainer = :trainer')
                ->setParameter('trainer', $filter['trainer']);
        }

        if(isset($filter['status'])) {
            $qb->andWhere('b.status = :status')
                ->setParameter('status', $filter['status']);
        }

        if(isset($filter['date'])) {
            $qb->andWhere('w.date = :date')
                ->setParameter('date', $filter['date']);
        }

        if(isset($filter['startTime'])) {
            $qb->andWhere('t.startTime = :startTime')
                ->setParameter('startTime', $filter['startTime']);
        }

        if(isset($filter['durationMinutes'])) {
            $qb->andWhere('t.durationMinutes = :durationMinutes')
                ->setParameter('durationMinutes', $filter['durationMinutes']);
        }

        return $qb;
    }

    private function generateCacheKey(GetClientBookings $query): string
    {
        $params = [
            'client' => $query->filter['client'],
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if(isset($query->filter['status'])) {
            $params['status'] = $query->filter['status'];
        }
        if(isset($query->filter['trainer'])) {
            $params['trainer'] = $query->filter['trainer'];
        }
        if(isset($query->filter['date'])) {
            $params['date'] = $query->filter['date'];
        }
        if(isset($query->filter['startTime'])) {
            $params['startTime'] = $query->filter['startTime'];
        }
        if(isset($query->filter['durationMinutes'])) {
            $params['durationMinutes'] = $query->filter['durationMinutes'];
        }

        return 'bookings_' . md5(serialize($params));
    }
}
