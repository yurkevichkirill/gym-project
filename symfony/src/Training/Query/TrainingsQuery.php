<?php

declare(strict_types=1);

namespace App\Training\Query;

use App\Request\SortParser;
use App\Training\DTO\ResolvedTrainingsRequestDTO;
use App\Training\Mapper\TrainingMapperInterface;
use App\Training\Repository\TrainingRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class TrainingsQuery
{
    private const array SORT_MAP = [
        'clientId' => 'c.id',
        'date' => 'w.date',
        'status' => 'b.status',
        'bookedAt' => 'b.bookedAt',
    ];

    public function __construct(
        private TrainingRepository $trainingRepo,
        private TrainingMapperInterface $mapper,
        private TagAwareCacheInterface $cache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function getCachedData(ResolvedTrainingsRequestDTO $dto, array $parsedSort): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->cache->get($cacheKey, function (ItemInterface $item, bool $save) use ($dto, $parsedSort): array {
            $item->expiresAfter(3600);

            if ($dto->trainer !== null) {
                $item->tag(['trainings_list_' . $dto->trainer->getId()]);
            } else {
                $item->tag(['trainings_list_all']);
            }

            $qb = $this->createQuery($dto);

            $totalQb = $this->createQuery($dto, true);
            $total = (int) $totalQb->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($parsedSort as $alias => $order) {
                $field = self::SORT_MAP[$alias] ?? "t.$alias";
                $qb->addOrderBy($field, $order);
            }

            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $trainings = $qb->getQuery()->getResult();

            $items = array_map(fn ($training) => $this->mapper->map($training), $trainings);

            return [
                'items' => $items,
                'total' => $total,
            ];
        });
    }

    private function createQuery(ResolvedTrainingsRequestDTO $dto, bool $isCount = false): QueryBuilder
    {
        $qb = $this->trainingRepo->createQueryBuilder('t')
            ->innerJoin('t.booking', 'b')
            ->innerJoin('t.trainerWorkTime', 'w')
            ->innerJoin('b.payment', 'p')
            ->innerJoin("b.client", 'c');

        if (!$isCount) {
            $qb->addSelect('b', 'w', 'p', 'c');
        }

        if ($dto->trainer) {
            $qb->andWhere('w.trainer = :trainer')
                ->setParameter('trainer', $dto->trainer);
        }

        if ($dto->client) {
            $qb->andWhere('c = :client')
                ->setParameter('client', $dto->client);
        }

        if ($dto->status !== null) {
            $qb->andWhere('b.status = :status')
                ->setParameter('status', $dto->status);
        }

        if ($dto->date !== null) {
            $qb->andWhere('w.date = :date')
                ->setParameter('date', $dto->date);
        }

        if ($dto->startTime !== null) {
            $qb->andWhere('t.startTime = :startTime')
                ->setParameter('startTime', $dto->startTime);
        }

        if ($dto->durationMinutes !== null) {
            $qb->andWhere('t.durationMinutes = :durationMinutes')
                ->setParameter('durationMinutes', $dto->durationMinutes);
        }

        if ($dto->isBusy !== null) {
            $qb->andWhere('t.isBusy = :isBusy')
                ->setParameter('isBusy', $dto->isBusy);
        }

        return $qb;
    }


    /**
     * @throws BadRequestHttpException
     */
    public function getParsedSort(ResolvedTrainingsRequestDTO $dto): array
    {
        return SortParser::parseSort($dto->sort, ResolvedTrainingsRequestDTO::ALLOWED_SORT_FIELDS);
    }

    private function generateCacheKey(ResolvedTrainingsRequestDTO $dto): string
    {
        $params = [
            'sort' => $dto->sort,
            'page' => $dto->page,
            'limit' => $dto->limit,
            'trainerId' => $dto->trainer?->getId(),
            'clientId' => $dto->client?->getId(),
            'status' => $dto->status,
            'date' => $dto->date?->format('Y-m-d'),
            'startTime' => $dto->startTime?->format('H:i:s'),
            'durationMinutes' => $dto->durationMinutes,
            'isBusy' => $dto->isBusy,
        ];

        return 'trainings_' . md5(json_encode($params));
    }
}
