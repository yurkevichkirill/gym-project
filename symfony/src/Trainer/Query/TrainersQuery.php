<?php

declare(strict_types=1);

namespace App\Trainer\Query;

use App\Request\SortParser;
use App\Trainer\DTO\ResolvedTrainersRequestDTO;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Repository\TrainerRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class TrainersQuery
{
    private const array SORT_MAP = [
        'trainingTypeId' => 'type.id',
    ];

    public function __construct(
        private TrainerRepository $repo,
        private TrainerMapperInterface $mapper,
        private TagAwareCacheInterface $cache
    ) {}

    /**
     * @param array<string, string> $parsedSort
     * @return array{items: list<mixed>, total: int}
     * @throws InvalidArgumentException
     */
    public function getCachedData(ResolvedTrainersRequestDTO $dto, array $parsedSort): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($dto, $parsedSort): array {
            $item->expiresAfter(3600);

            $item->tag(['trainers_list_public']);

            $qb = $this->createQuery(
                dto: $dto
            );

            $totalQb = $this->createQuery(
                dto: $dto,
                isCount: true
            );
            $total = (int) $totalQb->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

            foreach ($parsedSort as $field => $order) {
                $mapped = self::SORT_MAP[$field] ?? "t.$field";
                $qb->addOrderBy($mapped, $order);
            }

            $qb->setFirstResult(($dto->page - 1) * $dto->limit)
                ->setMaxResults($dto->limit);

            $trainers = $qb->getQuery()->getResult();

            $items = array_map(fn ($trainer) => $this->mapper->map($trainer), $trainers);

            return [
                'items' => $items,
                'total' => $total,
            ];
        });
    }

    private function createQuery(ResolvedTrainersRequestDTO $dto, bool $isCount = false): QueryBuilder
    {
        $qb = $this->repo->createQueryBuilder('t');

        $qb->andWhere('t.deletedAt IS NULL')
            ->andWhere('t.blockedAt IS NULL');

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

        return $qb;
    }

    /**
     * @return array<string, string>
     * @throws BadRequestHttpException
     */
    public function getParsedSort(ResolvedTrainersRequestDTO $dto): array
    {
        return SortParser::parseSort($dto->sort, ResolvedTrainersRequestDTO::ALLOWED_SORT_FIELDS);
    }

    private function generateCacheKey(ResolvedTrainersRequestDTO $dto): string
    {
        return 'trainers_' . hash('sha256', serialize([
            'sort' => $dto->sort,
            'page' => $dto->page,
            'limit' => $dto->limit,
            'minPricePerHour' => $dto->minPricePerHour,
            'maxPricePerHour' => $dto->maxPricePerHour,
            'trainingTypeId' => $dto->trainingType?->getId(),
        ]));
    }
}
