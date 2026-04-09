<?php

declare(strict_types=1);

namespace App\Client\Query;

use App\Client\DTO\GetClients;
use App\Client\Repository\ClientRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class ClientQuery
{
    public function __construct(
        private ClientRepository       $clientRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetClients $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($dto): array
        {
            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $alias => $order) {
                $qb->addOrderBy("c.$alias", $order);
            }

            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $item->tag(["clients_list"]);

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(array $filter): int
    {
        return $this->createQuery($filter, true)->select("COUNT(c.id)")->getQuery()->getSingleScalarResult();
    }

    private function createQuery(array $filter, bool $isCount = false): QueryBuilder
    {
        $qb = $this->clientRepo->createQueryBuilder('c');

        if (isset($filter['minAge'])) {
            $qb->andWhere('c.age >= :minAge')
                ->setParameter('minAge', $filter['minAge']);
        }

        if(isset($filter['maxAge'])) {
            $qb->andWhere('c.age <= :maxAge')
                ->setParameter('maxAge', $filter['maxAge']);
        }

        if(isset($filter['minBalance'])) {
            $qb->andWhere('c.balance >= :minBalance')
                ->setParameter('minBalance', $filter['minBalance']);
        }

        if(isset($filter['maxBalance'])) {
            $qb->andWhere('c.balance <= :maxBalance')
                ->setParameter('maxBalance', $filter['maxBalance']);
        }

        if (isset($filter['isDeleted'])) {
            if ($filter['isDeleted']) {
                $qb->andWhere('c.deletedAt IS NOT NULL');
            } else {
                $qb->andWhere('c.deletedAt IS NULL');
            }
        }

        return $qb;
    }

    private function generateCacheKey(GetClients $query): string
    {
        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if(isset($query->filter['minAge'])) {
            $params['minAge'] = $query->filter['minAge'];
        }
        if(isset($query->filter['maxAge'])) {
            $params['maxAge'] = $query->filter['maxAge'];
        }
        if(isset($query->filter['minBalance'])) {
            $params['minBalance'] = $query->filter['minBalance'];
        }
        if(isset($query->filter['maxBalance'])) {
            $params['maxBalance'] = $query->filter['maxBalance'];
        }
        if(isset($query->filter['isDeleted'])) {
            $params['isDeleted'] = $query->filter['isDeleted'];
        }

        return 'clients_' . md5(serialize($params));
    }
}
