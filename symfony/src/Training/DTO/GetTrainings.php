<?php

declare(strict_types=1);

namespace App\Training\DTO;

final readonly class GetTrainings
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        int $trainerId,
        string $sortRaw = 'bookedAt:ASC',
        ?int $clientId = null,
        ?string $date = null,
        ?string $durationMinutes = null,
        ?string $startTime = null,
        ?string $status = null,
        int $page = 1,
        int $limit = 20
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($trainerId, $clientId, $date, (int) $durationMinutes, $startTime, $status);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        int $trainerId,
        ?int $clientId,
        ?string $date = null,
        ?int $durationMinutes = null,
        ?string $startTime = null,
        ?string $status = null,
    ): array
    {
        $filter = ['trainerId' => $trainerId];
        if($clientId) {
            $filter['clientId'] = $clientId;
        }
        if($date) {
            $filter['date'] = $date;
        }
        if($durationMinutes) {
            $filter['durationMinutes'] = $durationMinutes;
        }
        if($startTime) {
            $filter['startTime'] = $startTime;
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
