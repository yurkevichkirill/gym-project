<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\MembershipPlan\DTO\GetMembershipPlansRequestDTO;
use App\MembershipPlan\DTO\MembershipPlanResponseDTO;
use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Mapper\MembershipPlanMapperInterface;
use App\MembershipPlan\Query\MembershipPlansQuery;
use App\Response\ResponseTypeDTO\CollectionResponse;
use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\SwaggerDocDTO\AbstractCollectionResponseDTO;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class MembershipPlanController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws BadRequestHttpException
     */
    #[Route('/api/membership/plans/', methods: ['GET'], format: 'json')]
    #[Cache(maxage: 0, smaxage: 3600, public: true, mustRevalidate: true)]
    #[OA\Get(
        operationId: 'getMembershipPlans',
        description: 'Retrieves a paginated and filterable list of all active membership plans. Available to all users (public).',
        summary: 'Get all available membership plans.',
        tags: ['Public: Membership Plans'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Collection of membership plans',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractCollectionResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: new Model(type: MembershipPlanResponseDTO::class))
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Invalid query parameters (e.g., unsupported sort field)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Unprocessable Entity - Validation failed for query parameters',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    public function getAll(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        GetMembershipPlansRequestDTO $queryDto,
        MembershipPlansQuery $handler,
    ): CollectionResponse {
        $parsedSort = $handler->getParsedSort($queryDto);

        $cachedData = $handler->getCachedData($queryDto, $parsedSort);

        return new CollectionResponse(
            $cachedData['items'],
            $queryDto->page,
            $queryDto->limit,
            $cachedData['total'],
            $parsedSort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/membership/plans/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getMembershipPlanById',
        description: 'Retrieves detailed information about a specific membership plan. Available to all users.',
        summary: 'Get a specific membership plan by ID.',
        tags: ['Public: Membership Plans'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Membership Plan ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership plan details',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: MembershipPlanResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Membership plan not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
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
