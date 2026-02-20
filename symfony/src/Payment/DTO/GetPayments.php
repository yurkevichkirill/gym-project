<?php

declare(strict_types=1);

namespace App\Payment\DTO;

final readonly class GetPayments
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        string $sortRaw = 'paidAt:ASC',
        ?int $trainerId = null,
        ?int $clientId = null,
        ?string $minAmount = null,
        ?string $maxAmount = null,
        ?string $category = null,
        ?string $status = null,
        int $page = 1,
        int $limit = 20
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($trainerId, $clientId, $minAmount, $maxAmount, $category, $status);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        ?int $trainerId = null,
        ?int $clientId = null,
        ?string $minAmount = null,
        ?string $maxAmount = null,
        ?string $category = null,
        ?string $status = null,
    ): array
    {
        $filter = [];
        if($clientId) {
            $filter['clientId'] = $clientId;
        }
        if($trainerId) {
            $filter['trainerId'] = $trainerId;
        }
        if($minAmount) {
            $filter['minAmount'] = $minAmount;
        }
        if($maxAmount) {
            $filter['maxAmount'] = $maxAmount;
        }
        if($category) {
            $filter['category'] = $category;
        }
        if($status) {
            $filter['status'] = $status;
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
