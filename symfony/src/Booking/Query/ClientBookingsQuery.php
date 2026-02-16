<?php

declare(strict_types=1);

namespace App\Booking\Query;

use App\Booking\DTO\GetClientBookings;
use App\Booking\Repository\BookingRepository;
use App\Client\Repository\ClientRepository;
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
        'trainerId' => 'trainer.id',
    ];

    public function __construct(
        private BookingRepository      $bookingRepo,
        private ClientRepository       $clientRepo,
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
            $client = $this->clientRepo->find($dto->filter['clientId']);

            $qb = $this->bookingRepo->createQueryBuilder('b')
                ->addSelect('t', 'w', 'trainer')
                ->leftJoin('b.training', 't')
                ->leftJoin('t.trainerWorkTime', 'w')
                ->leftJoin('w.trainer', 'trainer')
                ->andWhere('b.client = :client')
                ->setParameter('client', $client);

            if(isset($dto->filter['trainerId'])) {
                $qb->andWhere('trainer.id = :trainerId')
                    ->setParameter('trainerId', $dto->filter['trainerId']);
            }

            if(isset($dto->filter['status'])) {
                $qb->andWhere('b.status = :status')
                    ->setParameter('status', $dto->filter['status']);
            }

            if(isset($dto->filter['date'])) {
                $qb->andWhere('w.date = :date')
                    ->setParameter('date', $dto->filter['date']);
            }

            if(isset($dto->filter['startTime'])) {
                $qb->andWhere('t.startTime = :startTime')
                    ->setParameter('startTime', $dto->filter['startTime']);
            }

            if(isset($dto->filter['durationMinutes'])) {
                $qb->andWhere('t.durationMinutes = :durationMinutes')
                    ->setParameter('durationMinutes', $dto->filter['durationMinutes']);
            }

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

    private function generateCacheKey(GetClientBookings $query): string
    {
        $params = [
            'clientId' => $query->filter['clientId'],
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if(isset($query->filter['status'])) {
            $params['status'] = $query->filter['status'];
        }
        if(isset($query->filter['trainerId'])) {
            $params['trainerId'] = $query->filter['trainerId'];
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
