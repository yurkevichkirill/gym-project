<?php

declare(strict_types=1);

namespace App\Membership\DTO;

class GetMemberships
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        int $clientId,
        string $sortRaw = 'bookedAt:ASC',
        ?int $membershipPlanId = null,
        ?string $status = null,
        ?int $minVisits = null,
        ?int $maxVisits = null,
        int $page = 1,
        int $limit = 20
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($clientId, $membershipPlanId, $status, $minVisits, $maxVisits);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        int $clientId,
        ?int $membershipPlanId = null,
        ?string $status = null,
        ?int $minVisits = null,
        ?int $maxVisits = null,
    ): array
    {
        $filter = ['clientId' => $clientId];
        if($membershipPlanId) {
            $filter['membershipPlanId'] = $membershipPlanId;
        }
        if($status) {
            $filter['status'] = $status;
        }
        if($minVisits) {
            $filter['minVisits'] = $minVisits;
        }
        if($maxVisits) {
            $filter['maxVisits'] = $maxVisits;
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
