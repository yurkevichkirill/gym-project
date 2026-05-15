<?php

declare(strict_types=1);

namespace App\Client\Query;

use App\Client\DTO\GetClientsRequestDTO;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Repository\ClientRepository;
use App\Request\SortParser;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class ClientQuery
{
    public function __construct(
        private ClientRepository       $clientRepo,
        private ClientMapperInterface  $mapper,
        private TagAwareCacheInterface $cache,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function getCachedData(GetClientsRequestDTO $dto, array $parsedSort): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->cache->get($cacheKey, function (ItemInterface $item, bool $save) use ($dto, $parsedSort): array {
            $item->expiresAfter(3600);

            $item->tag(['clients_list']);

            $qb = $this->createQuery($dto);

            $totalQb = clone $qb;
            $total = (int) $totalQb->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();

            foreach ($parsedSort as $field => $order) {
                $qb->addOrderBy("c.$field", $order);
            }

            $qb->setFirstResult(($dto->page - 1) * $dto->limit)
                ->setMaxResults($dto->limit);

            $clients = $qb->getQuery()->getResult();

            $items = array_map(fn ($client) => $this->mapper->map($client), $clients);

            return [
                'items' => $items,
                'total' => $total,
            ];
        });
    }

    private function createQuery(GetClientsRequestDTO $dto): QueryBuilder
    {
        $qb = $this->clientRepo->createQueryBuilder('c');

        if ($dto->minAge !== null) {
            $qb->andWhere('c.age >= :minAge')
                ->setParameter('minAge', $dto->minAge);
        }

        if ($dto->maxAge !== null) {
            $qb->andWhere('c.age <= :maxAge')
                ->setParameter('maxAge', $dto->maxAge);
        }

        if ($dto->minBalance !== null) {
            $qb->andWhere('c.balance >= :minBalance')
                ->setParameter('minBalance', $dto->minBalance);
        }

        if ($dto->maxBalance !== null) {
            $qb->andWhere('c.balance <= :maxBalance')
                ->setParameter('maxBalance', $dto->maxBalance);
        }

        if ($dto->isDeleted !== null) {
            if ($dto->isDeleted) {
                $qb->andWhere('c.deletedAt IS NOT NULL');
            } else {
                $qb->andWhere('c.deletedAt IS NULL');
            }
        }

        return $qb;
    }

    /**
     * @throws BadRequestHttpException
     */
    public function getParsedSort(GetClientsRequestDTO $dto): array
    {
        return SortParser::parseSort($dto->sort, GetClientsRequestDTO::ALLOWED_SORT_FIELDS);
    }

    private function generateCacheKey(GetClientsRequestDTO $dto): string
    {
        $params = [
            'sort' => $dto->sort,
            'page' => $dto->page,
            'limit' => $dto->limit,
            'minAge' => $dto->minAge,
            'maxAge' => $dto->maxAge,
            'minBalance' => $dto->minBalance,
            'maxBalance' => $dto->maxBalance,
            'isDeleted' => $dto->isDeleted,
        ];

        return 'clients_' . md5(json_encode($params));
    }
}
