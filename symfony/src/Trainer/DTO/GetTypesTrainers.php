<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use App\Booking\Enum\BookingStatusEnum;
use App\TrainingType\Entity\TrainingType;

class GetTypesTrainers
{
    public array $sort;
    public array $filter;
    public int $page;
    public int $limit;

    public function __construct(
        ?int $minPrice = null,
        ?int $maxPrice = null,
        ?TrainingType $trainingType = null,
        string $sortRaw = 'createdAt:ASC',
        int $page = 1,
        int $limit = 20
    )
    {
        $this->sort = $this->parseSort($sortRaw);
        $this->filter = $this->putFilter($minPrice, $maxPrice, $trainingType);
        $this->page = $page;
        $this->limit = $limit;
    }

    private function putFilter (
        ?int $minPrice = null,
        ?int $maxPrice = null,
        ?TrainingType $trainingType = null,
    ): array
    {
        $filter = [];
        if($minPrice) {
            $filter['minPrice'] = $minPrice;
        }
        if($maxPrice) {
            $filter['maxPrice'] = $maxPrice;
        }
        if($trainingType) {
            $filter['trainingType'] = $trainingType;
        }

        return $filter;
    }

    private function parseSort(string $sortRaw): array
    {
        $sort = [];
        $allowedOrders = ['ASC', 'DESC'];
        $allowedParams = ['pricePerHour', 'firstName', 'lastName', 'trainingTypeId'];

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
