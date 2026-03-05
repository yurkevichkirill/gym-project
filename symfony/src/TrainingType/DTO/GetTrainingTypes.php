<?php

declare(strict_types=1);

namespace App\TrainingType\DTO;

final readonly class GetTrainingTypes
{
    public array $sort;
    public int $page;
    public int $limit;

    public function __construct(
        string $sortRaw = 'name:ASC',
        int $page = 1,
        int $limit = 20,
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->page = $page;
        $this->limit = $limit;
    }
    private function parseSort(string $sortRaw): array
    {
        $sort = [];
        $allowedOrders = ['ASC', 'DESC'];
        $allowedParams = ['name'];

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
