<?php

declare(strict_types=1);

namespace App\MembershipPlan\Query;

use App\MembershipPlan\DTO\GetMembershipPlans;
use App\MembershipPlan\DTO\MembershipPlanFilter;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class MembershipPlansQuery
{
    public function __construct(
        private MembershipPlanRepository $repo,
        private TagAwareCacheInterface $cache
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetMembershipPlans $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->cache->get($cacheKey, function (CacheItem $item) use ($dto) {
            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto->filter);

            foreach ($dto->sort as $field => $order) {
                $qb->addOrderBy("m.$field", $order);
            }

            $qb->setFirstResult(($dto->page - 1) * $dto->limit)
                ->setMaxResults($dto->limit);

            $item->tag(['membership_plans_list']);

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(MembershipPlanFilter $filter): int
    {
        return (int) $this->createQuery($filter)
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQuery(MembershipPlanFilter $filter): QueryBuilder
    {
        $qb = $this->repo->createQueryBuilder('m');

        if ($filter->minPrice !== null) {
            $qb->andWhere('m.price >= :minPrice')
                ->setParameter('minPrice', $filter->minPrice);
        }

        if ($filter->maxPrice !== null) {
            $qb->andWhere('m.price <= :maxPrice')
                ->setParameter('maxPrice', $filter->maxPrice);
        }

        if ($filter->minDurationDays !== null) {
            $qb->andWhere('m.durationDays >= :minDurationDays')
                ->setParameter('minDurationDays', $filter->minDurationDays);
        }

        if ($filter->maxDurationDays !== null) {
            $qb->andWhere('m.durationDays <= :maxDurationDays')
                ->setParameter('maxDurationDays', $filter->maxDurationDays);
        }

        if ($filter->minSessionLimit !== null) {
            $qb->andWhere('m.sessionLimit >= :minSessionLimit')
                ->setParameter('minSessionLimit', $filter->minSessionLimit);
        }

        if ($filter->maxSessionLimit !== null) {
            $qb->andWhere('m.sessionLimit <= :maxSessionLimit')
                ->setParameter('maxSessionLimit', $filter->maxSessionLimit);
        }

        if ($filter->isUnlimited === true) {
            $qb->andWhere('m.sessionLimit IS NULL');
        }

        if ($filter->isUnlimited === false) {
            $qb->andWhere('m.sessionLimit IS NOT NULL');
        }

        return $qb;
    }

    private function generateCacheKey(GetMembershipPlans $dto): string
    {
        return 'membership_plans_' . md5(serialize([
                'sort' => $dto->sort,
                'page' => $dto->page,
                'limit' => $dto->limit,
                'minPrice' => $dto->filter->minPrice,
                'maxPrice' => $dto->filter->maxPrice,
                'minDurationDays' => $dto->filter->minDurationDays,
                'maxDurationDays' => $dto->filter->maxDurationDays,
                'minSessionLimit' => $dto->filter->minSessionLimit,
                'maxSessionLimit' => $dto->filter->maxSessionLimit,
                'isUnlimited' => $dto->filter->isUnlimited,
            ]));
    }
}
