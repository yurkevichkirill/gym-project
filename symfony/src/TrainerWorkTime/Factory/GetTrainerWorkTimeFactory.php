<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Factory;

use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\GetTrainerWorkTime;
use App\TrainerWorkTime\DTO\WorkTimeFilter;
use DateTimeImmutable;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class GetTrainerWorkTimeFactory
{
    public function fromRequest(Request $request, ?Trainer $trainer = null): GetTrainerWorkTime
    {
        $date = $this->parseDate($request->query->get('date'));

        return new GetTrainerWorkTime(
            sort: $this->parseSort($request->query->get('sort', 'date:ASC')),
            filter: new WorkTimeFilter(
                trainer: $trainer,
                date: $date,
            ),
            page: (int)$request->query->get('page', 1),
            limit: (int)$request->query->get('limit', 20),
        );
    }

    private function parseDate(?string $input): ?DateTimeImmutable
    {
        if (!$input) {
            return null;
        }

        try {
            return new DateTimeImmutable($input);
        } catch (Exception) {
            throw new BadRequestHttpException('Invalid date format');
        }
    }

    private function parseSort(string $sortRaw): array
    {
        $allowedOrders = ['ASC', 'DESC'];
        $allowedParams = ['date', 'startTime', 'endTime'];

        $sort = [];

        foreach (explode(',', $sortRaw) as $item) {
            $exploded = explode(':', $item);

            $field = $exploded[0] ?? null;

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
