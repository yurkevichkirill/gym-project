<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Repository\BookingRepository;
use App\Booking\Service\BookingServiceInterface;
use App\Client\Repository\ClientRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class BookingService implements BookingServiceInterface
{
    public function __construct(
        private ClientRepository $clientRepo,
        private BookingRepository $bookingRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function findBy(int $clientId, array $sort, ?BookingStatusEnum $status = null): array
    {
        $cacheKey = $this->generateCacheKey($clientId, $sort, $status);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($clientId, $sort, $status): array
        {
            $client = $this->clientRepo->find($clientId);
            if(is_null($client)) {
                return [];
            }
            $criteria = ['client' => $client];
            if($status) {
                $criteria['status'] = $status;
            }

            $item->tag(['booking_list']);

            return $this->bookingRepo->findBy($criteria, $sort);
        });
    }

    public function generateCacheKey(int $clientId, array $sort, ?BookingStatusEnum $status): string
    {
        $params = [
            'clientId' => $clientId,
            'sort' => $sort,
            'status' => $status
        ];

        return 'bookings_' . md5(serialize($params));
    }
}
