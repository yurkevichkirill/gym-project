<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use App\Booking\Enum\BookingStatusEnum;

class GetTypesTrainers
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        ?int $trainingTypeId = null,
        string $sortRaw = 'createdAt:ASC',
        int $page = 1,
        int $limit = 20
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $trainingTypeId ? ['trainingTypeId' => $trainingTypeId] : [];
        $this->page = $page;
        $this->limit = $limit;
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
