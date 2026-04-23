<?php

declare(strict_types=1);

namespace App\Client\Query;

use App\Client\DTO\ClientFilter;
use App\Client\DTO\GetClients;
use App\Client\Repository\ClientRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class ClientQuery
{
    public function __construct(
        private ClientRepository $clientRepo,
        private TagAwareCacheInterface $gymCache
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetClients $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (ItemInterface $item, bool $save) use ($dto): array {
            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto->filter);

            foreach ($dto->sort as $field => $order) {
                $qb->addOrderBy("c.$field", $order);
            }

            $qb->setFirstResult(($dto->page - 1) * $dto->limit)
                ->setMaxResults($dto->limit);

            $item->tag(['clients_list']);

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(ClientFilter $filter): int
    {
        return (int) $this->createQuery($filter, true)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQuery(ClientFilter $filter, bool $isCount = false): QueryBuilder
    {
        $qb = $this->clientRepo->createQueryBuilder('c');

        if ($filter->minAge !== null) {
            $qb->andWhere('c.age >= :minAge')
                ->setParameter('minAge', $filter->minAge);
        }

        if ($filter->maxAge !== null) {
            $qb->andWhere('c.age <= :maxAge')
                ->setParameter('maxAge', $filter->maxAge);
        }

        if ($filter->minBalance !== null) {
            $qb->andWhere('c.balance >= :minBalance')
                ->setParameter('minBalance', $filter->minBalance);
        }

        if ($filter->maxBalance !== null) {
            $qb->andWhere('c.balance <= :maxBalance')
                ->setParameter('maxBalance', $filter->maxBalance);
        }

        if ($filter->isDeleted !== null) {
            if ($filter->isDeleted) {
                $qb->andWhere('c.deletedAt IS NOT NULL');
            } else {
                $qb->andWhere('c.deletedAt IS NULL');
            }
        }

        return $qb;
    }

    private function generateCacheKey(GetClients $query): string
    {
        $f = $query->filter;

        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
            'minAge' => $f->minAge,
            'maxAge' => $f->maxAge,
            'minBalance' => $f->minBalance,
            'maxBalance' => $f->maxBalance,
            'isDeleted' => $f->isDeleted,
        ];

        return 'bookings_' . md5(json_encode($params));
    }
}
