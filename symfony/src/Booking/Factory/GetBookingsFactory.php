<?php

declare(strict_types=1);

namespace App\Booking\Factory;

use App\Booking\DTO\BookingFilter;
use App\Booking\DTO\GetBookings;
use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetBookingsFactory
{
    public function __construct(
        private TrainerRepository $trainerRepo,
        private ClientRepository  $clientRepo,
    ) {}

    public function fromRequest(Request $request, ?Client $client = null, ?Trainer $trainer = null): GetBookings
    {
        if ($trainer === null) {
            if ($trainerId = $request->query->get('trainerId')) {
                $trainer = $this->trainerRepo->find((int)$trainerId);

                if (!$trainer) {
                    throw new NotFoundHttpException('Trainer not found');
                }
            }
        }

        if ($client === null) {
            if ($clientId = $request->query->get('clientId')) {
                $client = $this->clientRepo->find((int) $clientId);

                if (!$client) {
                    throw new NotFoundHttpException('Client not found');
                }
            }
        }

        $statusInput = $request->query->get('status');
        $status = $statusInput ? BookingStatusEnum::tryFrom($statusInput) : null;

        if ($statusInput && !$status) {
            throw new BadRequestHttpException('Invalid status');
        }

        $date = $this->parseDate($request->query->get('date'));
        $startTime = $this->parseTime($request->query->get('startTime'));

        $durationMinutes = $this->toInt($request->query->get('durationMinutes'));

        $filter = new BookingFilter(
            client: $client,
            trainer: $trainer,
            status: $status,
            date: $date,
            startTime: $startTime,
            durationMinutes: $durationMinutes,
        );

        return new GetBookings(
            sort: $this->parseSort($request->query->get('sort', 'bookedAt:ASC')),
            filter: $filter,
            page: (int)$request->query->get('page', 1),
            limit: (int)$request->query->get('limit', 20),
        );
    }

    private function parseDate(?string $input): ?DateTimeImmutable
    {
        if (!$input) return null;

        try {
            return new DateTimeImmutable($input);
        } catch (\Exception) {
            throw new BadRequestHttpException('Invalid date format');
        }
    }

    private function parseTime(?string $input): ?DateTimeImmutable
    {
        if (!$input) return null;

        $time = DateTimeImmutable::createFromFormat('H:i:s', $input);

        if (!$time) {
            throw new BadRequestHttpException('Invalid time format, expected H:i:s');
        }

        return $time;
    }

    private function toInt(?string $value): ?int
    {
        return $value !== null ? (int)$value : null;
    }

    private function parseSort(string $sortRaw): array
    {
        $sort = [];
        $allowedOrders = ['ASC', 'DESC'];
        $allowedParams = ['bookedAt', 'status', 'trainingId', 'date', 'startTime', 'durationMinutes'];

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
