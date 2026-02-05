<?php

declare(strict_types=1);

namespace App\Client\Service;

use App\Client\Repository\ClientRepository;
use App\Client\Service\ClientServiceInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class ClientService implements ClientServiceInterface
{
    public function __construct(
        private ClientRepository $clientRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function findBy(array $sort): array
    {
        $cacheKey = $this->generateCacheKey($sort);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($sort): array {
            $item->tag(['clients_list']);

            return $this->clientRepo->findBy([], $sort);
        });
    }

    public function generateCacheKey(array $sort): string
    {
        $params = [
            'orderBy' => $sort
        ];

        return 'clients_' . md5(serialize($params));
    }
}
