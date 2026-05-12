<?php

declare(strict_types=1);

namespace App\Membership\Factory;

use App\Client\Entity\Client;
use App\Membership\DTO\GetMemberships;
use App\Membership\DTO\MembershipFilter;
use App\Membership\Enum\MembershipStatusEnum;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\Request\Utils\RequestParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetMembershipsFactory
{
    public function __construct(
        private MembershipPlanRepository $membershipPlanRepo,
        private RequestParser $parser,
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
            minVisits: $this->parser->toInt($request->query->get('minVisits')),
            maxVisits: $this->parser->toInt($request->query->get('maxVisits')),
        );

        $allowedSortParams = ['startDate', 'endDate', 'status', 'visits', 'membershipPlanId'];

        return new GetMemberships(
            sort: $this->parser->parseSort($request->query->get('sort', 'startDate:ASC'), $allowedSortParams),
            filter: $filter,
            page: $this->parser->toPositiveInt($request->query->get('page'), 'page', 1),
            limit: min($this->parser->toPositiveInt($request->query->get('limit'), 'limit', 20), 20),
        );
    }
}
