<?php

declare(strict_types=1);

namespace App\Training\Factory;

use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\Training\DTO\GetTrainings;
use App\Training\DTO\TrainingFilter;
use DateTimeImmutable;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetTrainingsFactory
{
    public function __construct(
        private ClientRepository $clientRepo,
        private TrainerRepository $trainerRepo,
    ) {}

    public function fromRequest(Request $request, ?Trainer $trainer = null, ?Client $client = null): GetTrainings
    {
        if ($client === null) {
            if ($clientId = $request->query->get('clientId')) {
                $client = $this->clientRepo->find((int)$clientId);

                if (!$client) {
                    throw new NotFoundHttpException('Client not found');
                }
            }
        }

        if ($trainer === null) {
            if ($trainerId = $request->query->get('trainerId')) {
                $trainer = $this->trainerRepo->find((int)$trainerId);

                if (!$trainer) {
                    throw new NotFoundHttpException('Trainer not found');
                }
            }
        }

        $statusInput = $request->query->get('status');
        $status = $statusInput ? BookingStatusEnum::tryFrom($statusInput) : null;

        if ($statusInput && !$status) {
            throw new BadRequestHttpException('Invalid status');
        }

        $filter = new TrainingFilter(
            trainer: $trainer,
            client: $client,
            date: $this->parseDate($request->query->get('date')),
            durationMinutes: $this->toInt($request->query->get('durationMinutes')),
            startTime: $this->parseTime($request->query->get('startTime')),
            status: $request->query->get('status'),
        );

        return new GetTrainings(
            sort: $this->parseSort($request->query->get('sort', 'bookedAt:ASC')),
            filter: $filter,
            page: (int)$request->query->get('page', 1),
            limit: (int)$request->query->get('limit', 20),
        );
    }

    private function toInt(?string $value): ?int
    {
        return $value !== null ? (int)$value : null;
    }

    private function parseDate(?string $input): ?DateTimeImmutable
    {
        if (!$input) return null;

        try {
            return new DateTimeImmutable($input);
        } catch (Exception) {
            throw new BadRequestHttpException('Invalid date format');
        }
    }

    private function parseTime(?string $input): ?DateTimeImmutable
    {
        if (!$input) return null;

        try {
            return new DateTimeImmutable($input);
        } catch (Exception) {
            throw new BadRequestHttpException('Invalid time format');
        }
    }

    private function parseSort(string $sortRaw): array
    {
        $allowedOrders = ['ASC', 'DESC'];
        $allowedParams = ['startTime', 'durationMinutes', 'clientId', 'date', 'status', 'bookedAt'];

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
