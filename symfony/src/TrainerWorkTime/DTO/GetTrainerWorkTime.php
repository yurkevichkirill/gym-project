<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\DTO;

use App\Trainer\Entity\Trainer;

final readonly class GetTrainerWorkTime
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        Trainer $trainer,
        ?string $date = null,
        string $sortRaw = 'date:ASC',
        int $page = 1,
        int $limit = 20
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($trainer, $date);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        Trainer $trainer,
        ?string $date,
    ): array
    {
        $filter = ['trainer' => $trainer];
        if($date) {
            $filter['date'] = $date;
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
