<?php

declare(strict_types=1);

namespace App\MembershipPlan\Factory;

use App\MembershipPlan\DTO\GetMembershipPlansDTO;
use App\MembershipPlan\DTO\MembershipPlanFilter;
use App\Request\Utils\RequestParser;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetMembershipPlansFactory
{
    public function __construct(
        private RequestParser $parser
    ) {}

    public function fromRequest(Request $request): GetMembershipPlansDTO
    {
        $filter = new MembershipPlanFilter(
            minPrice: $this->parser->toInt($request->query->get('minPrice')),
            maxPrice: $this->parser->toInt($request->query->get('maxPrice')),
            minDurationDays: $this->parser->toInt($request->query->get('minDurationDays')),
            maxDurationDays: $this->parser->toInt($request->query->get('maxDurationDays')),
            minSessionLimit: $this->parser->toInt($request->query->get('minSessionLimit')),
            maxSessionLimit: $this->parser->toInt($request->query->get('maxSessionLimit')),
            isUnlimited: $this->parser->toBool($request->query->get('isUnlimited')),
        );

        $allowedSortParams = ['durationDays', 'price', 'sessionLimit'];

        return new GetMembershipPlansDTO(
            sort: $this->parser->parseSort($request->query->get('sort', 'durationDays:ASC'),$allowedSortParams),
            filter: $filter,
            page: $this->parser->toPositiveInt($request->query->get('page'), 'page', 1),
            limit: min($this->parser->toPositiveInt($request->query->get('limit'), 'limit', 20), 20),
        );
    }
}
