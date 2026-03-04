<?php

declare(strict_types=1);

namespace App\Membership\DTO;

use App\Client\Entity\Client;
use App\MembershipPlan\Entity\MembershipPlan;

class GetMemberships
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        Client $client,
        string $sortRaw = 'bookedAt:ASC',
        ?MembershipPlan $membershipPlan = null,
        ?string $status = null,
        ?int $minVisits = null,
        ?int $maxVisits = null,
        int $page = 1,
        int $limit = 20
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($client, $membershipPlan, $status, $minVisits, $maxVisits);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        Client $client,
        ?MembershipPlan $membershipPlan = null,
        ?string $status = null,
        ?int $minVisits = null,
        ?int $maxVisits = null,
    ): array
    {
        $filter = ['client' => $client];
        if($membershipPlan) {
            $filter['membershipPlan'] = $membershipPlan;
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
