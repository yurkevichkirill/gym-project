<?php

declare(strict_types=1);

namespace App\Membership\Factory;

use App\Client\Entity\Client;
use App\Membership\DTO\GetMemberships;
use App\Membership\DTO\MembershipFilter;
use App\Membership\Enum\MembershipStatusEnum;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetMembershipsFactory
{
    public function __construct(
        private MembershipPlanRepository $membershipPlanRepo,
    ) {}

    public function fromRequest(Request $request, ?Client $client = null): GetMemberships
    {
        $statusInput = $request->query->get('status');
        $status = $statusInput ? MembershipStatusEnum::tryFrom($statusInput) : null;

        if ($statusInput && !$status) {
            throw new BadRequestHttpException('Invalid status');
        }

        $membershipPlan = null;

        if ($planId = $request->query->get('membershipPlanId')) {
            $membershipPlan = $this->membershipPlanRepo->find((int)$planId);

            if (!$membershipPlan) {
                throw new NotFoundHttpException('Membership plan not found');
            }
        }

        $filter = new MembershipFilter(
            client: $client,
            membershipPlan: $membershipPlan,
            status: $request->query->get('status'),
            minVisits: $this->toInt($request->query->get('minVisits')),
            maxVisits: $this->toInt($request->query->get('maxVisits')),
        );

        return new GetMemberships(
            sort: $this->parseSort($request->query->get('sort', 'startDate:ASC')),
            filter: $filter,
            page: (int)$request->query->get('page', 1),
            limit: (int)$request->query->get('limit', 20),
        );
    }

    private function toInt(?string $value): ?int
    {
        return $value !== null ? (int)$value : null;
    }

    private function parseSort(string $sortRaw): array
    {
        $sort = [];
        $allowedOrders = ['ASC', 'DESC'];
        $allowedParams = ['startDate', 'endDate', 'status', 'visits', 'membershipPlanId'];

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
