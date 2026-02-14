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
        'trainingId' => 't.id'
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
            $client = $this->clientRepo->find($dto->clientId);
            if(is_null($client)) {
                return [];
            }

            $qb = $this->bookingRepo->createQueryBuilder('b')
                ->leftJoin('b.training', 't')
                ->andWhere('b.client = :client')
                ->setParameter('client', $client);

            if($dto->filter['status']) {
                $qb->andWhere('b.status = :status')
                    ->setParameter('status', $dto->filter['status']);
            }

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $alias => $order) {
                $field = self::SORT_MAP[$alias] ?? "b.$alias";
                $qb->addOrderBy("$field", $order);
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $item->tag(['booking_list']);

            return $qb->getQuery()->getResult();
        });
    }

    private function generateCacheKey(GetClientBookings $query): string
    {
        $params = [
            'clientId' => $query->clientId,
            'sort' => $query->sort,
            'status' => $query->filter['status'],
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        return 'bookings_' . md5(serialize($params));
    }
}
