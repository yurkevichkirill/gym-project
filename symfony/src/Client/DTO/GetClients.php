<?php

declare(strict_types=1);

namespace App\Client\DTO;

final readonly class GetClients
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        string $sortRaw = 'bookedAt:ASC',
        ?int $minAge = null,
        ?int $maxAge = null,
        ?string $minBalance = null,
        ?string $maxBalance = null,
        ?bool $isDeleted = null,
        int $page = 1,
        int $limit = 20,
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($minAge, $maxAge, $minBalance, $maxBalance, $isDeleted);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        ?int $minAge = null,
        ?int $maxAge = null,
        ?string $minBalance = null,
        ?string $maxBalance = null,
        ?bool $isDeleted = null,
    ): array
    {
        $filter = [];

        if ($minAge !== null) {
            $filter = ['minAge' => $minAge];
        }
        if ($maxAge !== null) {
            $filter = ['maxAge' => $maxAge];
        }
        if ($minBalance !== null) {
            $filter['minBalance'] = $minBalance;
        }
        if ($maxBalance !== null) {
            $filter['maxBalance'] = $maxBalance;
        }
        if ($isDeleted !== null) {
            $filter['isDeleted'] = $isDeleted;
        }

        return $filter;
    }

    private function parseSort(string $sortRaw): array
    {
        $sort = [];
        $allowedOrders = ['ASC', 'DESC'];
        $allowedParams = ['firstName', 'lastName', 'balance', 'age', 'createdAt', 'updatedAt', 'deletedAt'];

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
