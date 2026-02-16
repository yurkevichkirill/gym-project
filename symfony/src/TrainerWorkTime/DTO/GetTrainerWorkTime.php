<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\DTO;

use DateTimeImmutable;

final readonly class GetTrainerWorkTime
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        int $trainerId,
        ?DateTimeImmutable $date = null,
        string             $sortRaw = 'date:ASC',
        int                $page = 1,
        int                $limit = 20
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        if($date) {
            $this->filter = [
                "trainerId" => $trainerId,
                "date" => $date,
            ];
        } else {
            $this->filter = [
                "trainerId" => $trainerId,
            ];
        }
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
