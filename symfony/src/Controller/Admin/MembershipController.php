<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Client\Entity\Client;
use App\Membership\DTO\CreateMembershipRequestDTO;
use App\Membership\DTO\MembershipResponseDTO;
use App\Membership\DTO\ResolvedMembershipsRequestDTO;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Mapper\MembershipMapperInterface;
use App\Membership\Query\MembershipQuery;
use App\Membership\Service\MembershipManager;
use App\Response\ResponseTypeDTO\CollectionResponse;
use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\SwaggerDocDTO\AbstractCollectionResponseDTO;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use App\User\Enum\UserRolesEnum;
use DateMalformedStringException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class MembershipController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws BadRequestHttpException
     */
    #[Route('/api/memberships/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetMemberships',
        summary: 'Get all memberships (Admin).',
        tags: ['Admin: Memberships'],
        parameters: [
            new OA\Parameter(name: 'membershipPlanId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'clientId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: MembershipStatusEnum::class)),
            new OA\Parameter(name: 'minVisits', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'maxVisits', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', example: 'startDate:ASC')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Collection of memberships',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractCollectionResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: new Model(type: MembershipResponseDTO::class))
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid query parameters',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Client or Membership Plan not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function getAll(
        ResolvedMembershipsRequestDTO $resolvedDto,
        MembershipQuery $handler,
    ): CollectionResponse {
        $parsedSort = $handler->getParsedSort($resolvedDto);

        $cachedData = $handler->getCachedData($resolvedDto, $parsedSort);

        return new CollectionResponse(
            $cachedData['items'],
            $resolvedDto->page,
            $resolvedDto->limit,
            $cachedData['total'],
            $parsedSort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/memberships/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetMembershipById',
        summary: 'Get membership details (Admin).',
        tags: ['Admin: Memberships'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership details',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: MembershipResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function get(
        Membership $membership,
        MembershipMapperInterface $mapper
    ): ItemResponse {
        return new ItemResponse(
            data: $mapper->map($membership),
            status: Response::HTTP_OK
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/clients/{id}/membership/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCreateMembership',
        description: 'Assigns a membership plan to a client and creates a corresponding payment record.',
        summary: 'Create a new membership for a client (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateMembershipRequestDTO::class))
        ),
        tags: ['Admin: Memberships'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Client ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Membership created successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: MembershipResponseDTO::class)
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
                description: 'Forbidden - Admin only or Client is blocked/inactive',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Client or Membership Plan not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Client already has an active membership',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function create(
        Client                                          $client,
        #[MapRequestPayload] CreateMembershipRequestDTO $requestDto,
        MembershipMapperInterface                       $mapper,
        MembershipManager                               $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($client, $requestDto->membershipPlanId));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_CREATED);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/memberships/{id}/freeze/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminFreezeMembership',
        description: 'Suspends an active membership and changes its status to frozen.',
        summary: 'Freeze membership (Admin).',
        tags: ['Admin: Memberships'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Membership ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership successfully frozen',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: MembershipResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Membership is not in ACTIVE status',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function freeze(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager
    ): ItemResponse {
        $responseDto = $mapper->map($manager->freeze($membership));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/memberships/{id}/unfreeze/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminUnfreezeMembership',
        description: 'Calculates the freeze duration, extends the membership end date, and restores ACTIVE status.',
        summary: 'Unfreeze membership (Admin).',
        tags: ['Admin: Memberships'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Membership ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership successfully unfrozen',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: MembershipResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Membership is not currently frozen',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function unfreeze(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager
    ): ItemResponse {
        $responseDto = $mapper->map($manager->unfreeze($membership));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws Throwable
     */
    #[Route('/api/memberships/{id}/renew/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminRenewMembership',
        description: 'Extends or creates a renewal for an existing membership based on its current plan.',
        summary: 'Renew a membership (Admin).',
        tags: ['Admin: Memberships'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Membership ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership successfully renewed',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: MembershipResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Membership cannot be renewed in its current state',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function renew(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager
    ): ItemResponse {
        $responseDto = $mapper->map($manager->renew($membership));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/memberships/{id}/terminate/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminTerminateMembership',
        description: 'Immediately terminates the membership by setting its end date to now and status to EXPIRED.',
        summary: 'Terminate membership (Admin).',
        tags: ['Admin: Memberships'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Membership ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership successfully terminated',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: MembershipResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Membership is already expired',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function terminate(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager
    ): ItemResponse {
        $responseDto = $mapper->map($manager->terminate($membership));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }
}
