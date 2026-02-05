<?php

declare(strict_types=1);

namespace App\MembershipPlan\Service;

use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\MembershipPlan\Service\MembershipPlanServiceInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class MembershipPlanService implements MembershipPlanServiceInterface
{
    public function __construct(
        private MembershipPlanRepository $membershipPlanRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function findBy(array $sort, ?int $sessionLimit): array
    {
        $cacheKey = $this->generateCacheKey($sort, $sessionLimit);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($sort, $sessionLimit): array
        {
            $item->tag(['membership_plans_list']);

            $criteria = [];
            if($sessionLimit === 0) {
                $criteria['sessionLimit'] = null;
            } else if($sessionLimit) {
                $criteria['sessionLimit'] = $sessionLimit;
            }

            return $this->membershipPlanRepo->findBy($criteria, $sort);
        });
    }

    public function generateCacheKey(array $sort, ?int $sessionLimit): string
    {
        $params = [
            'sort' => $sort,
            'sessionLimit' => $sessionLimit
        ];

        return 'membership_plans_' . md5(serialize($params));
    }
}
