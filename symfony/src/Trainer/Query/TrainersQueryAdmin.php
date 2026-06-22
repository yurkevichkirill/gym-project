<?php

declare(strict_types=1);

namespace App\Trainer\Query;

use App\Request\SortParser;
use App\Infrastructure\Doctrine\SoftDeleteableFilterScope;
use App\Trainer\DTO\ResolvedTrainersRequestAdminDTO;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Repository\TrainerRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class TrainersQueryAdmin
{
    private const array SORT_MAP = [
        'trainingTypeId' => 'type.id',
    ];

    public function __construct(
        private TrainerRepository $repo,
        private TrainerMapperInterface $mapper,
        private TagAwareCacheInterface $cache,
        private SoftDeleteableFilterScope $softDeleteableFilterScope,
    ) {}

    /**
     * @param array<string, string> $parsedSort
     * @return array{items: list<mixed>, total: int}
     * @throws InvalidArgumentException
     */
    public function getCachedData(ResolvedTrainersRequestAdminDTO $dto, array $parsedSort): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($dto, $parsedSort): array {
            $item->expiresAfter(3600);
            $item->tag(['trainers_list']);

            $loadData = function () use ($dto, $parsedSort): array {
                $qb = $this->createQuery(
                    dto: $dto,
                );

                $totalQb = $this->createQuery(
                    dto: $dto,
                    isCount: true,
                );

                $total = (int) $totalQb
                    ->select('COUNT(t.id)')
                    ->getQuery()
                    ->getSingleScalarResult();

                foreach ($parsedSort as $field => $order) {
                    $mapped = self::SORT_MAP[$field] ?? "t.$field";
                    $qb->addOrderBy($mapped, $order);
                }

                $qb->setFirstResult(($dto->page - 1) * $dto->limit)
                    ->setMaxResults($dto->limit);

                $trainers = $qb->getQuery()->getResult();

                $items = array_map(
                    fn ($trainer) => $this->mapper->map($trainer, true),
                    $trainers,
                );

                return [
                    'items' => $items,
                    'total' => $total,
                ];
            };

            if ($dto->isDeleted === true) {
                return $this->softDeleteableFilterScope->run($loadData);
            }

            return $loadData();
        });
    }

    private function createQuery(ResolvedTrainersRequestAdminDTO $dto, bool $isCount = false): QueryBuilder
    {
        $qb = $this->repo->createQueryBuilder('t');

        if (!$isCount) {
            $qb->leftJoin('t.trainingType', 'type')
                ->addSelect('type');
        }

        if ($dto->minPricePerHour !== null) {
            $qb->andWhere('t.pricePerHour >= :minPricePerHour')
                ->setParameter('minPricePerHour', $dto->minPricePerHour);
        }

        if ($dto->maxPricePerHour !== null) {
            $qb->andWhere('t.pricePerHour <= :maxPricePerHour')
                ->setParameter('maxPricePerHour', $dto->maxPricePerHour);
        }

        if ($dto->trainingType !== null) {
            $qb->andWhere('t.trainingType = :trainingType')
                ->setParameter('trainingType', $dto->trainingType);
        }

        if ($dto->maxBalance !== null) {
            $qb->andWhere('t.balance <= :maxBalance')
                ->setParameter('maxBalance', $dto->maxBalance);
        }

        if ($dto->minBalance !== null) {
            $qb->andWhere('t.balance >= :minBalance')
                ->setParameter('minBalance', $dto->minBalance);
        }

        if ($dto->isBlocked !== null) {
            if ($dto->isBlocked) {
                $qb->andWhere('t.blockedAt IS NOT NULL');
            } else {
                $qb->andWhere('t.blockedAt IS NULL');
            }
        }

        if ($dto->isDeleted !== null) {
            if ($dto->isDeleted) {
                $qb->andWhere('t.deletedAt IS NOT NULL');
            } else {
                $qb->andWhere('t.deletedAt IS NULL');
            }
        }

        return $qb;
    }

    /**
     * @return array<string, string>
     * @throws BadRequestHttpException
     */
    public function getParsedSort(ResolvedTrainersRequestAdminDTO $dto): array
    {
        return SortParser::parseSort($dto->sort, ResolvedTrainersRequestAdminDTO::ALLOWED_SORT_FIELDS);
    }

    private function generateCacheKey(ResolvedTrainersRequestAdminDTO $dto): string
    {
        return 'trainers_' . hash('sha256', serialize([
            'sort' => $dto->sort,
            'page' => $dto->page,
            'limit' => $dto->limit,
            'minPricePerHour' => $dto->minPricePerHour,
            'maxPricePerHour' => $dto->maxPricePerHour,
            'trainingTypeId' => $dto->trainingType?->getId(),
            'minBalance' => $dto->minBalance,
            'maxBalance' => $dto->maxBalance,
            'isDeleted' => $dto->isDeleted,
            'isBlocked' => $dto->isBlocked,
        ]));
    }
}
