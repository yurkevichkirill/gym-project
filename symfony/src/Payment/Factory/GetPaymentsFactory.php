<?php

declare(strict_types=1);

namespace App\Payment\Factory;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Payment\DTO\GetPayments;
use App\Payment\DTO\PaymentFilter;
use App\Payment\Enum\PaymentStatusEnum;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use DateTimeImmutable;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetPaymentsFactory
{
    public function __construct(
        private ClientRepository $clientRepo,
        private TrainerRepository $trainerRepo,
    )
    {}

    public function fromRequest(Request $request, ?Trainer $trainer = null, ?Client $client = null): GetPayments
    {
        if ($client === null) {
            if ($clientId = $request->query->get('clientId')) {
                $client = $this->clientRepo->find((int) $clientId);

                if (!$client) {
                    throw new NotFoundHttpException("Client not found");
                }
            }
        }

        if ($trainer === null) {
            if ($trainerId = $request->query->get('trainerId')) {
                $trainer = $this->trainerRepo->find((int) $trainerId);

                if (!$trainer) {
                    throw new NotFoundHttpException("Trainer not found");
                }
            }
        }

        $raw = $request->query->get('isRefund');

        $isRefund = null;

        if ($raw !== null) {
            $isRefund = filter_var(
                $raw,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($isRefund === null && !in_array(strtolower($raw), ['true', 'false'], true)) {
                throw new BadRequestHttpException('Invalid isRefund value');
            }
        }

        $statusInput = $request->query->get('status');
        $status = $statusInput ? PaymentStatusEnum::tryFrom($statusInput) : null;

        if ($statusInput && !$status) {
            throw new BadRequestHttpException('Invalid status');
        }

        $minCreatedAt = $this->parseDate($request->query->get('minCreateAt'));
        $maxCreatedAt = $this->parseDate($request->query->get('maxCreateAt'));

        $filter = new PaymentFilter(
            client: $client,
            trainer: $trainer,
            minAmount: $this->toInt($request->query->get('minAmount')),
            maxAmount: $this->toInt($request->query->get('maxAmount')),
            isRefund: $isRefund,
            status: $status,
            minCreatedAt: $minCreatedAt,
            maxCreatedAt: $maxCreatedAt,
        );

        return new GetPayments(
            sort: $this->parseSort($request->query->get('sort', 'paidAt:ASC')),
            filter: $filter,
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', 20),
        );
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

    private function toInt(?string $value): ?int
    {
        return $value !== null ? (int)$value : null;
    }

    private function parseSort(string $sortRaw): array
    {
        $sort = [];
        $allowedOrders = ['ASC', 'DESC'];
        $allowedParams = ['amount', 'category', 'paidAt', 'status', 'isRefund', 'createAt'];

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
