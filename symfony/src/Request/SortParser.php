<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class SortParser
{
    /**
     * @throws BadRequestHttpException
     */
    public static function parseSort(string $sortRaw, array $allowedParams): array
    {
        $sort = [];
        $allowedOrders = ['ASC', 'DESC'];

        foreach (explode(',', $sortRaw) as $item) {
            $exploded = explode(':', $item);

            $field = $exploded[0];

            if (!$field || !in_array($field, $allowedParams, true)) {
                throw new BadRequestHttpException("Invalid sort field: $field");
            }

            $order = strtoupper(trim($exploded[1] ?? 'ASC'));

            if (!in_array($order, $allowedOrders, true)) {
                throw new BadRequestHttpException("Invalid sort order: $order");
            }

            $sort[$field] = $order;
        }

        return $sort;
    }
}
