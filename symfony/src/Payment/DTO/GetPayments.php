<?php

declare(strict_types=1);

namespace App\Payment\DTO;

use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;

final readonly class GetPayments
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        string $sortRaw = 'paidAt:ASC',
        ?Trainer $trainer = null,
        ?Client $client = null,
        ?string $minAmount = null,
        ?string $maxAmount = null,
        ?string $category = null,
        int $page = 1,
        int $limit = 20
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($trainer, $client, $minAmount, $maxAmount, $category);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        ?Trainer $trainer = null,
        ?Client $client = null,
        ?string $minAmount = null,
        ?string $maxAmount = null,
        ?string $category = null,
    ): array
    {
        $filter = [];
        if($client) {
            $filter['client'] = $client;
        }
        if($trainer) {
            $filter['trainer'] = $trainer;
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
