<?php

declare(strict_types=1);

namespace App\Training\DTO;

use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;

final readonly class GetTrainings
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        string $sortRaw = 'bookedAt:ASC',
        ?Client $client = null,
        ?string $date = null,
        ?string $durationMinutes = null,
        ?string $startTime = null,
        ?string $status = null,
        int $page = 1,
        int $limit = 20,
        ?Trainer $trainer = null,
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($trainer, $client, $date, $durationMinutes, $startTime, $status);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        ?Trainer $trainer,
        ?Client $client,
        ?string $date = null,
        ?int $durationMinutes = null,
        ?string $startTime = null,
        ?string $status = null,
    ): array
    {
        $filter = [];

        if ($trainer !== null) {
            $filter = ['trainer' => $trainer];
        }
        if ($client !== null) {
            $filter['client'] = $client;
        }
        if ($date !== null) {
            $filter['date'] = $date;
        }
        if ($durationMinutes !== null) {
            $filter['durationMinutes'] = $durationMinutes;
        }
        if ($startTime !== null) {
            $filter['startTime'] = $startTime;
        }
        if ($status !== null) {
            $filter['status'] = $status;
        }

        return $filter;
    }

    private function parseSort(string $sortRaw): array
    {
        $sort = [];
        $allowedOrders = ['ASC', 'DESC'];
        $allowedParams = ['startTime', 'durationMinutes', 'clientId', 'date', 'status', 'bookedAt'];

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
