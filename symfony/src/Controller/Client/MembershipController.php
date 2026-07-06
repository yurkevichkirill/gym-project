<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Client\Entity\Client;
use App\Membership\DTO\CreateMembershipRequestDTO;
use App\Membership\DTO\MembershipResponseDTO;
use App\Membership\DTO\ResolvedMembershipsRequestDTO;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Mapper\MembershipMapperInterface;
use App\Membership\Query\MembershipQuery;
use App\Membership\Security\MembershipVoter;
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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class MembershipController extends AbstractController
{
    /**
     * @throws BadRequestHttpException
     */
    #[Route('/api/me/memberships/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getClientMemberships',
        summary: 'Get a list of current client memberships.',
        tags: ['Client: Memberships'],
        parameters: [
            new OA\Parameter(name: 'membershipPlanId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: MembershipStatusEnum::class)),
            new OA\Parameter(name: 'minVisits', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'maxVisits', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'startDate:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Collection of client memberships',
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
                description: 'Membership Plan not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_CLIENT->value)]
    public function getAll(
        ResolvedMembershipsRequestDTO $resolvedDto,
        MembershipQuery $handler,
    ): CollectionResponse {

        $parsedSort = $handler->getParsedSort($resolvedDto);

        $data = $handler->getData($resolvedDto, $parsedSort);

        return new CollectionResponse(
            $data['items'],
            $resolvedDto->page,
            $resolvedDto->limit,
            $data['total'],
            $parsedSort,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route('/api/me/memberships/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getClientMembershipById',
        summary: 'Get membership details (Client).',
        tags: ['Client: Memberships'],
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
                description: 'Forbidden - Access denied',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_CLIENT->value)]
    public function get(Membership $membership, MembershipMapperInterface $mapper): ItemResponse
    {
        $this->denyAccessUnlessGranted(MembershipVoter::VIEW_OWN, $membership);

        return new ItemResponse(data: $mapper->map($membership), status: Response::HTTP_OK);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/me/membership/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'createMembership',
        description: 'Creates a new membership for the authenticated client and initiates the payment process.',
        summary: 'Purchase a new membership plan.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateMembershipRequestDTO::class))
        ),
        tags: ['Client: Memberships'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Membership purchased successfully.',
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
                description: 'Forbidden - User is blocked or inactive',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership plan not found',
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
    #[IsGranted(UserRolesEnum::ROLE_CLIENT->value)]
    public function create(
        #[CurrentUser] Client                           $client,
        #[MapRequestPayload] CreateMembershipRequestDTO $requestDto,
        MembershipMapperInterface                       $mapper,
        MembershipManager                               $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($client, $requestDto->membershipPlanId));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_CREATED);
    }

    /**
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/me/memberships/{id}/cancel/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'cancelMyMembership',
        description: 'Cancels a pending membership that is awaiting payment.',
        summary: 'Cancel a pending membership (Client).',
        tags: ['Client: Memberships'],
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
                description: 'Membership successfully canceled.',
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
                description: 'Forbidden - Access denied',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Membership is not in PENDING status.',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_CLIENT->value)]
    public function cancel(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager
    ): ItemResponse {
        $this->denyAccessUnlessGranted(MembershipVoter::EDIT_OWN, $membership);

        $responseDto = $mapper->map($manager->cancel($membership));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/me/memberships/{id}/freeze/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'getClientFreezeMembership',
        description: 'Suspends an active membership and changes its status to frozen.',
        summary: 'Freeze membership (Client).',
        tags: ['Client: Memberships'],
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
                description: 'Forbidden - Access denied',
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
    #[IsGranted(UserRolesEnum::ROLE_CLIENT->value)]
    public function freeze(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager
    ): ItemResponse {
        $this->denyAccessUnlessGranted(MembershipVoter::EDIT_OWN, $membership);

        $responseDto = $mapper->map($manager->freeze($membership));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/me/memberships/{id}/unfreeze/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'getClientUnfreezeMembership',
        description: 'Calculates the freeze duration, extends the membership end date, and restores ACTIVE status.',
        summary: 'Unfreeze a frozen membership (Client).',
        tags: ['Client: Memberships'],
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
                description: 'Membership unfrozen successfully.',
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
                description: 'Forbidden - Access denied',
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
    #[IsGranted(UserRolesEnum::ROLE_CLIENT->value)]
    public function unfreeze(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager
    ): ItemResponse {
        $this->denyAccessUnlessGranted(MembershipVoter::EDIT_OWN, $membership);

        $responseDto = $mapper->map($manager->unfreeze($membership));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws Throwable
     */
    #[Route('/api/me/memberships/{id}/renew/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'getClientRenewMembership',
        description: 'Extends or creates a renewal for an existing membership based on its current plan.',
        summary: 'Renew a membership (Client).',
        tags: ['Client: Memberships'],
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
                description: 'Forbidden - Access denied',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership or Plan not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Membership cannot be renewed in its current state',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_CLIENT->value)]
    public function renew(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager
    ): ItemResponse {
        $this->denyAccessUnlessGranted(MembershipVoter::EDIT_OWN, $membership);

        $responseDto = $mapper->map($manager->renew($membership));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/me/memberships/{id}/terminate/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'getClientTerminateMembership',
        description: 'Immediately terminates the membership by setting its status to EXPIRED and end date to now.',
        summary: 'Terminate a membership (Client).',
        tags: ['Client: Memberships'],
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
                description: 'Membership successfully terminated.',
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
                description: 'Forbidden - Access denied',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Membership not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Membership already EXPIRED.',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_CLIENT->value)]
    public function terminate(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager
    ): ItemResponse {
        $this->denyAccessUnlessGranted(MembershipVoter::EDIT_OWN, $membership);

        $responseDto = $mapper->map($manager->terminate($membership));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }
}
