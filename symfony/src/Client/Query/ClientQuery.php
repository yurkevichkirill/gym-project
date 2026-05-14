<?php

declare(strict_types=1);

namespace App\Client\Query;

use App\Client\DTO\GetClientsRequestDTO;
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
        private ClientRepository $clientRepo,
        private TagAwareCacheInterface $gymCache
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetClientsRequestDTO $dto, array $parsedSort): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (ItemInterface $item, bool $save) use ($dto, $parsedSort): array {
            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto);

            foreach ($parsedSort as $field => $order) {
                $qb->addOrderBy("c.$field", $order);
            }

            $qb->setFirstResult(($dto->page - 1) * $dto->limit)
                ->setMaxResults($dto->limit);

            $item->tag(['clients_list']);

            return $qb->getQuery()->getResult();
        });
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getTotal(GetClientsRequestDTO $dto): int
    {
        return (int) $this->createQuery($dto)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
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
