<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\MembershipPlan\DTO\CreateMembershipPlanRequest;
use App\MembershipPlan\DTO\MembershipPlanResponse;
use App\MembershipPlan\DTO\UpdateMembershipPlanRequest;
use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Mapper\MembershipPlanMapper;
use App\MembershipPlan\Service\MembershipPlanManager;
use App\Response\DTO\AbstractItemResponseDTO;
use App\Response\DTO\ErrorResponseDTO;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MembershipPlanController extends AbstractController
{
    #[Route('/api/membership/plans/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCreateMembershipPlan',
        description: 'Creates a new membership plan with specified pricing, duration, and session limits.',
        summary: 'Create a new membership plan (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateMembershipPlanRequest::class))
        ),
        tags: ['Admin: Membership Plans'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Membership plan created successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: MembershipPlanResponse::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Invalid input data',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Admin access required',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload] CreateMembershipPlanRequest $requestDto,
        MembershipPlanManager                            $manager,
        MembershipPlanMapper                             $mapper,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_CREATED,
        );
    }

    #[Route('/api/membership/plans/{id}/', methods: ['PATCH', 'PUT'], format: 'json')]
    #[OA\Put(
        operationId: 'adminUpdateMembershipPlan',
        description: 'Updates specific fields of an existing membership plan.',
        summary: 'Update an existing membership plan (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateMembershipPlanRequest::class))
        ),
        tags: ['Admin: Membership Plans'],
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
                description: 'Membership plan updated successfully.',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: MembershipPlanResponse::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Invalid input data',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Admin access required',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership plan not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        #[MapRequestPayload] UpdateMembershipPlanRequest $requestDto,
        MembershipPlan                                   $membershipPlan,
        MembershipPlanManager                            $manager,
        MembershipPlanMapper                             $mapper,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->update($requestDto, $membershipPlan));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/membership/plans/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Delete(
        operationId: 'adminDeleteMembershipPlan',
        description: 'Deletes a specific membership plan. Cannot be deleted if it is currently assigned to any client memberships.',
        summary: 'Delete a membership plan (Admin).',
        tags: ['Admin: Membership Plans'],
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
                response: 204,
                description: 'Membership plan deleted successfully (No Content)'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Admin access required',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership plan not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function remove(
        MembershipPlanManager $membershipPlanManager,
        MembershipPlan $membershipPlan,
    ): NoContentResponse {
        $membershipPlanManager->remove($membershipPlan);

        return new NoContentResponse();
    }
}
