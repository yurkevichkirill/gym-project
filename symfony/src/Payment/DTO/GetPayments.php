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
        Client $client,
        string $sortRaw = 'paidAt:ASC',
        ?Trainer $trainer = null,
        ?string $minAmount = null,
        ?string $maxAmount = null,
        ?string $category = null,
        int $page = 1,
        int $limit = 20
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($client, $trainer, $minAmount, $maxAmount, $category);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        Client $client,
        ?Trainer $trainer = null,
        ?string $minAmount = null,
        ?string $maxAmount = null,
        ?string $category = null,
    ): array
    {
        $filter['client'] = $client;

        if($trainer !== null) {
            $filter['trainer'] = $trainer;
        }
        if($minAmount !== null) {
            $filter['minAmount'] = $minAmount;
        }
        if($maxAmount !== null) {
            $filter['maxAmount'] = $maxAmount;
        }
        if($category !== null) {
            $filter['category'] = $category;
        }

        return $filter;
    }

    private function parseSort(string $sortRaw): array
    {
        $sort = [];
        $allowedOrders = ['ASC', 'DESC'];
        $allowedParams = ['amount', 'category', 'paidAt'];

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
