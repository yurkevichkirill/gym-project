<?php

namespace App\Controller\Public;

use App\MembershipPlan\DTO\MembershipPlanResponse;
use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Factory\GetMembershipPlansFactory;
use App\MembershipPlan\Mapper\MembershipPlanMapperInterface;
use App\MembershipPlan\Query\MembershipPlansQuery;
use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class MembershipPlanController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/membership/plans/', methods: ['GET'], format: 'json')]
    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
    #[OA\Get(
        operationId: 'getMembershipPlans',
        summary: 'Get all available membership plans.',
        tags: ['All: MembershipPlan'],
        parameters: [
            new OA\Parameter(name: 'minPrice', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'maxPrice', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'minDurationDays', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'minSessionLimit', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'isUnlimited', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'durationDays:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: MembershipPlanResponse::class))
                        ),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                    ]
                )
            )
        ]
    )]
    public function getAll(
        Request $request,
        MembershipPlanMapperInterface $mapper,
        MembershipPlansQuery $handler,
        GetMembershipPlansFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request);
        $plans = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($plan) => $mapper->map($plan), $plans),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/membership/plans/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getMembershipPlan',
        summary: 'Get a specific membership plan by ID.',
        tags: ['All: MembershipPlan'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Success',
                content: new OA\JsonContent(ref: new Model(type: MembershipPlanResponse::class))
            ),
            new OA\Response(response: 404, description: 'Membership plan not found')
        ]
    )]
    public function get(
        MembershipPlan $membershipPlan,
        MembershipPlanMapperInterface $mapper,
    ): ItemResponse {
        return new ItemResponse(
            data: $mapper->map($membershipPlan),
            status: Response::HTTP_OK,
        );
    }
}
