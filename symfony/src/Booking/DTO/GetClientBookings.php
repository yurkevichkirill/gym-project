<?php

declare(strict_types=1);

namespace App\Booking\DTO;

use App\Booking\Enum\BookingStatusEnum;

final readonly class GetClientBookings
{
    public int $clientId;
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        int $clientId,
        string $sortRaw = 'bookedAt:ASC',
        ?BookingStatusEnum $status = null,
        int $page = 1,
        int $limit = 20
    )
    {
        $this->clientId = $clientId;
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $status ? ['status' => $status] : [];
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
