<?php

declare(strict_types=1);

namespace App\Request\Utils;

use DateTimeImmutable;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class RequestParser
{
    public function parseDate(?string $input): ?DateTimeImmutable
    {
        if (!$input) return null;

        try {
            return new DateTimeImmutable($input);
        } catch (Exception) {
            throw new BadRequestHttpException('Invalid date format');
        }
    }

    public function parseTime(?string $input): ?DateTimeImmutable
    {
        if (!$input) return null;

        $time = DateTimeImmutable::createFromFormat('H:i:s', $input);

        if (!$time) {
            throw new BadRequestHttpException('Invalid time format, expected H:i:s');
        }

        return $time;
    }

    public function toInt(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $result = filter_var($value, FILTER_VALIDATE_INT);

        if ($result === false) {
            throw new BadRequestHttpException("Invalid integer: $value");
        }

        return $result;
    }

    public function toPositiveInt(?string $value, string $fieldName = 'value'): ?int
    {
        $result = $this->toInt($value);

        if ($result === null) {
            return null;
        }

        if ($result <= 0) {
            throw new BadRequestHttpException("$fieldName must be a positive integer");
        }

        return $result;
    }

    public function toBool(?string $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        throw new BadRequestHttpException('Invalid boolean value, expected true or false');
    }

    public function parseSort(string $sortRaw, $allowedParams): array
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
