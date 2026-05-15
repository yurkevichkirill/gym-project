<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Entity\Admin;
use App\Client\DTO\AdminUpdateClientRequest;
use App\Client\DTO\ClientResponse;
use App\Client\DTO\CreateClientRequest;
use App\Client\DTO\GetClientsRequestDTO;
use App\Client\Entity\Client;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Query\ClientQuery;
use App\Client\Service\ClientManager;
use App\Exception\NoActiveMembershipException;
use App\ImportJob\DTO\ClientImportResponseDTO;
use App\ImportJob\DTO\CreateClientImportBatch;
use App\ImportJob\Message\ImportMessage;
use App\ImportJob\Service\ImportService;
use App\Membership\DTO\MembershipResponse;
use App\Membership\Mapper\MembershipMapperInterface;
use App\Response\CollectionResponse;
use App\Response\DTO\AbstractCollectionResponseDTO;
use App\Response\DTO\AbstractItemResponseDTO;
use App\Response\DTO\ErrorResponseDTO;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ClientController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws BadRequestHttpException
     */
    #[Route('/api/clients/', name: 'app_api_clients', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetAllClients',
        summary: 'Get all clients with filters (Admin).',
        tags: ['Admin: Clients'],
        parameters: [
            new OA\Parameter(name: 'minAge', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'maxAge', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'minBalance', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'maxBalance', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'isDeleted', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(
                name: 'sort',
                description: 'Sort field and order. Allowed fields: firstName, lastName, balance, age, createdAt, updatedAt, deletedAt.',
                in: 'query',
                schema: new OA\Schema(type: 'string'),
                example: 'age:ASC'
            ),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Collection of clients',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractCollectionResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: new Model(type: ClientResponse::class))
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid query parameters (e.g. validation constraint violation)',
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
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        GetClientsRequestDTO $requestDTO,
        ClientQuery $handler,
    ): CollectionResponse {
        $parsedSort = $handler->getParsedSort($requestDTO);

        $cachedData = $handler->getCachedData($requestDTO, $parsedSort);

        return new CollectionResponse(
            $cachedData['items'],
            $requestDTO->page,
            $requestDTO->limit,
            $cachedData['total'],
            $parsedSort,
            Response::HTTP_OK
        );
    }

    #[Route('/api/clients/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetClient',
        summary: 'Get client details (Admin).',
        tags: ['Admin: Clients'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client details',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: ClientResponse::class)
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
                description: 'Client not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function get(Client $client, ClientMapperInterface $mapper): ItemResponse
    {
        return new ItemResponse(data: $mapper->map($client), status: Response::HTTP_OK);
    }

    #[Route('/api/clients/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCreateClient',
        summary: 'Create a new client (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateClientRequest::class))
        ),
        tags: ['Admin: Clients'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Client successfully created',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: ClientResponse::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid JSON payload',
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
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload] CreateClientRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($requestDto));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_CREATED);
    }

    #[Route('/api/clients/{id}/', methods: ['PATCH'], format: 'json')]
    #[OA\Patch(
        operationId: 'adminUpdateClient',
        summary: 'Update client details (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: AdminUpdateClientRequest::class))
        ),
        tags: ['Admin: Clients'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client successfully updated',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: ClientResponse::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid JSON payload',
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
                description: 'Client not found',
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
        Client $client,
        #[MapRequestPayload] AdminUpdateClientRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->updateByAdmin($client, $requestDto));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws ConflictHttpException
     */
    #[Route('/api/clients/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Delete(
        operationId: 'adminDeleteClient',
        summary: 'Soft delete a client (Admin).',
        tags: ['Admin: Clients'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Client successfully deleted (No Content)'
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
                description: 'Client not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Client is already deleted',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Client $client,
        ClientManager $manager,
    ): NoContentResponse {
        $manager->softDelete($client);

        return new NoContentResponse();
    }

    #[Route('/api/clients/{id}/restore/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminRestoreClient',
        summary: 'Restore a soft-deleted client (Admin).',
        tags: ['Admin: Clients'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client successfully restored',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: ClientResponse::class)
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
                description: 'Client not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function restore(Client $client, ClientManager $manager, ClientMapperInterface $mapper): ItemResponse
    {
        $responseDto = $mapper->map($manager->restore($client));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK
        );
    }

    /**
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     */
    #[Route('/api/clients/{id}/block/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminBlockClient',
        summary: 'Block a client account (Admin).',
        tags: ['Admin: Clients'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client successfully blocked',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: ClientResponse::class)
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
                description: 'Client not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Client already blocked',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function block(Client $client, #[CurrentUser] Admin $admin, ClientMapperInterface $mapper, ClientManager $manager): ItemResponse
    {
        $responseDto = $mapper->map($manager->block($admin, $client));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws ConflictHttpException
     */
    #[Route('/api/clients/{id}/unblock/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminUnblockClient',
        summary: 'Unblock a client account (Admin).',
        tags: ['Admin: Clients'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client successfully unblocked',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: ClientResponse::class)
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
                description: 'Client not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Client is not blocked',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function unblock(
        Client $client,
        ClientMapperInterface $mapper,
        ClientManager $manager
    ): ItemResponse {
        $responseDto = $mapper->map($manager->unblock($client));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws NoActiveMembershipException
     * @throws AccessDeniedHttpException
     */
    #[Route('/api/clients/{id}/visit/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminRegisterVisit',
        summary: 'Register a gym visit for a client (Admin).',
        tags: ['Admin: Clients'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Visit registered successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: MembershipResponse::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - No active membership',
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
                description: 'Client not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function visit(
        Client $client,
        MembershipMapperInterface $mapper,
        ClientManager $manager
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->visit($client));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/api/import/clients/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminImportClients',
        summary: 'Import clients batch (Async).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateClientImportBatch::class))
        ),
        tags: ['Admin: Clients'],
        responses: [
            new OA\Response(
                response: 202,
                description: 'Accepted - Import job queued.',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: ClientImportResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid JSON payload',
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
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function import(
        #[MapRequestPayload] CreateClientImportBatch $requestDto,
        ImportService $importService,
        MessageBusInterface $bus,
    ): ItemResponse {
        $job = $importService->create($requestDto);

        foreach ($requestDto->clients as $rowIndex => $clientDto) {
            $bus->dispatch(new ImportMessage($clientDto, $job->getId(), $rowIndex));
        }

        return new ItemResponse(
            data: new ClientImportResponseDTO(
                status: 'queued',
                count: count($requestDto->clients),
                jobId: $job->getId(),
            ),
            status: Response::HTTP_ACCEPTED,
        );
    }
}
