<?php

declare(strict_types=1);

namespace App\MembershipPlan\DTO;

class GetMembershipPlans
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        ?int $minPrice = null,
        ?int $maxPrice = null,
        ?int $durationDays = null,
        ?int $sessionLimit = null,
        string $sortRaw = 'durationDays:ASC',
        int $page = 1,
        int $limit = 20,
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($minPrice, $maxPrice, $durationDays, $sessionLimit);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        ?int $minPrice,
        ?int $maxPrice,
        ?int $durationDays,
        ?int $sessionLimit,
    ): array
    {
        $filter = [];
        if($minPrice) {
            $filter['minPrice'] = $minPrice;
        }
        if($maxPrice) {
            $filter['maxPrice'] = $maxPrice;
        }
        if($durationDays) {
            $filter['durationDays'] = $durationDays;
        }
        if($sessionLimit) {
            $filter['sessionLimit'] = $sessionLimit;
        }

        return $filter;
    }

    private function parseSort(string $sortRaw): array
    {
        $sort = [];
        $allowedOrders = ['ASC', 'DESC'];
        $allowedParams = ['durationDays', 'price', 'sessionLimit'];

        foreach (explode(',', $sortRaw) as $item) {
            $exploded = explode(':', $item);

            if (!in_array($exploded[0], $allowedParams)) {
                continue;
            }

            if (count($exploded) === 1) {
                $exploded[] = 'ASC';
            }
            
            [$field, $rawOrder] = $exploded;
            $order = strtoupper(trim($rawOrder));

            if (!in_array($order, $allowedOrders)) {
                continue;
            }

            $sort[$field] = $order;
        }

        return $sort;
    }
}
