<?php

declare(strict_types=1);

namespace App\MembershipPlan\Query;

use App\MembershipPlan\DTO\GetMembershipPlans;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class MembershipPlansQuery
{
    public function __construct(
        private MembershipPlanRepository $membershipPlanRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetMembershipPlans $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($dto): array
        {
            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $field => $order) {
                $qb->addOrderBy("m.$field", $order);
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $item->tag(['membership_plans_list']);

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(array $filter): int
    {
        return $this->createQuery($filter)->select("COUNT(m.id)")->getQuery()->getSingleScalarResult();
    }

    private function createQuery(array $filter): QueryBuilder
    {
        $qb = $this->membershipPlanRepo->createQueryBuilder('m');

        if(isset($filter['minPrice'])) {
            $qb->andWhere('m.price >= :minPrice')
                ->setParameter('minPrice', $filter['minPrice']);
        }

        if(isset($filter['maxPrice'])) {
            $qb->andWhere('m.price <= :maxPrice')
                ->setParameter('maxPrice', $filter['maxPrice']);
        }

        if(isset($filter['durationDays'])) {
            $qb->andWhere('m.durationDays = :durationDays')
                ->setParameter('durationDays', $filter['durationDays']);
        }

        if(isset($filter['sessionLimit'])) {
            $qb->andWhere('m.sessionLimit = :sessionLimit')
                ->setParameter('sessionLimit', $filter['sessionLimit']);
        }

        return $qb;
    }

    private function generateCacheKey(GetMembershipPlans $query): string
    {
        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if(isset($query->filter['minPrice'])) {
            $params['minPrice'] = $query->filter['minPrice'];
        }
        if(isset($query->filter['maxPrice'])) {
            $params['maxPrice'] = $query->filter['maxPrice'];
        }
        if(isset($query->filter['durationDays'])) {
            $params['durationDays'] = $query->filter['durationDays'];
        }
        if(isset($query->filter['sessionLimit'])) {
            $params['sessionLimit'] = $query->filter['sessionLimit'];
        }

        return 'membership_plans_' . md5(serialize($params));
    }
}
