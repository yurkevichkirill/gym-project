<?php

declare(strict_types=1);

namespace App\MembershipPlan\Query;

use App\MembershipPlan\DTO\GetMembershipPlans;
use App\MembershipPlan\Repository\MembershipPlanRepository;
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
            $qb = $this->membershipPlanRepo->createQueryBuilder('m');

            if(isset($dto->filter['durationDays'])) {
                $qb->andWhere('m.durationDays = :durationDays')
                    ->setParameter('durationDays', $dto->filter['durationDays']);
            }

            if(isset($dto->filter['sessionLimit'])) {
                $qb->andWhere('m.sessionLimit = :sessionLimit')
                    ->setParameter('sessionLimit', $dto->filter['sessionLimit']);
            }

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

    private function generateCacheKey(GetMembershipPlans $query): string
    {
        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if(isset($query->filter['durationDays'])) {
            $params['durationDays'] = $query->filter['durationDays'];
        }
        if(isset($query->filter['sessionLimit'])) {
            $params['sessionLimit'] = $query->filter['sessionLimit'];
        }

        return 'membership_plans_' . md5(serialize($params));
    }
}
