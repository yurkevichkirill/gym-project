<?php

declare(strict_types=1);

namespace App\Booking\Query;

use App\Booking\DTO\GetClientBookings;
use App\Booking\Repository\BookingRepository;
use App\Client\Repository\ClientRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class ClientBookingsQuery
{
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

        $sort = $this->parseSort($dto->sort);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($dto, $sort): array
        {
            $client = $this->clientRepo->find($dto->clientId);
            if(is_null($client)) {
                return [];
            }

            $qb = $this->bookingRepo->createQueryBuilder('b')
                ->andWhere('b.client = :client')
                ->setParameter('client', $client);

            if($dto->status) {
                $qb->andWhere('b.status = :status')
                    ->setParameter('status', $dto->status);
            }

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($sort as $field => $order) {
                $qb->addOrderBy("b.$field, $order");
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $item->tag(['booking_list']);

            return $qb->getQuery()->getArrayResult();
        });
    }

    private function generateCacheKey(GetClientBookings $query): string
    {
        $params = [
            'clientId' => $query->clientId,
            'sort' => $query->sort,
            'status' => $query->status,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        return 'bookings_' . md5(serialize($params));
    }

    private function parseSort(string $sortRaw): array
    {
        $sort = [];
        $allowedOrders = ['ASC', 'DESC'];

        foreach (explode(',', $sortRaw) as $item) {
            [$field, $rawOrder] = explode(':', $item);
            $order = strtoupper(trim($rawOrder));

            if (!in_array($order, $allowedOrders)) {
                continue;
            }

            $sort[$field] = $order;
        }

        return $sort ?: ['bookedAt' => 'ASC'];
    }

}
