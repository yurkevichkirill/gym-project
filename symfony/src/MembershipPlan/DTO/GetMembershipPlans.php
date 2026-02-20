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
        ?int $durationDays = null,
        ?int $sessionLimit = null,
        string $sortRaw = 'durationDays:ASC',
        int $page = 1,
        int $limit = 20,
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($durationDays, $sessionLimit);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        ?int $durationDays,
        ?int $sessionLimit,
    ): array
    {
        $filter = [];
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

        foreach (explode(',', $sortRaw) as $item) {
            [$field, $rawOrder] = explode(':', $item);
            $order = strtoupper(trim($rawOrder));

            if (!in_array($order, $allowedOrders)) {
                continue;
            }

            $sort[$field] = $order;
        }

        return $sort;
    }
}
