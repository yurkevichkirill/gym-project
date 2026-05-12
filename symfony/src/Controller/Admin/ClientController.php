<?php

namespace App\Controller\Admin;

use App\Admin\Entity\Admin;
use App\Client\DTO\AdminUpdateClientRequest;
use App\Client\DTO\ClientResponse;
use App\Client\DTO\CreateClientRequest;
use App\Client\Entity\Client;
use App\Client\Factory\GetClientFactory;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Query\ClientQuery;
use App\Client\Service\ClientManager;
use App\ImportJob\DTO\ClientImportResponseDTO;
use App\ImportJob\DTO\CreateClientImportBatch;
use App\ImportJob\Message\ImportMessage;
use App\ImportJob\Service\ImportService;
use App\Membership\DTO\MembershipResponse;
use App\Membership\Mapper\MembershipMapperInterface;
use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ClientController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/clients/', name: 'app_api_clients', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetAllClients',
        summary: 'Get all clients with filters (Admin).',
        tags: ['Admin: Client'],
        parameters: [
            new OA\Parameter(name: 'minAge', in: 'query', schema: new OA\Schema(type: 'integer'), example: 18),
            new OA\Parameter(name: 'maxAge', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'minBalance', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'isDeleted', in: 'query', schema: new OA\Schema(type: 'string', enum: ['true', 'false'])),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'age:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: ClientResponse::class))),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        ClientMapperInterface $mapper,
        Request $request,
        ClientQuery $handler,
        GetClientFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request);
        $clients = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn($client) => $mapper->map($client), $clients),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK
        );
    }

    #[Route('/api/clients/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetClient',
        summary: 'Get client details (Admin).',
        tags: ['Admin: Client'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(ref: new Model(type: ClientResponse::class))),
            new OA\Response(response: 404, description: 'Client not found')
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
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: CreateClientRequest::class))),
        tags: ['Admin: Client'],
        responses: [
            new OA\Response(response: 200, description: 'Client created', content: new OA\JsonContent(ref: new Model(type: ClientResponse::class))),
            new OA\Response(response: 400, description: 'Invalid data'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload] CreateClientRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($requestDto));
        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    #[Route('/api/clients/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\Put(
        operationId: 'adminUpdateClient',
        summary: 'Update client details (Admin).',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: AdminUpdateClientRequest::class))),
        tags: ['Admin: Client'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(ref: new Model(type: ClientResponse::class))),
            new OA\Response(response: 403, description: 'Forbidden - User blocked or inactive'),
            new OA\Response(response: 404, description: 'Client not found'),
            new OA\Response(response: 422, description: 'Validation failed')
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/api/clients/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Delete(
        operationId: 'adminDeleteClient',
        summary: 'Soft delete a client (Admin).',
        tags: ['Admin: Client'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'No Content'),
            new OA\Response(response: 403, description: 'Forbidden - Cannot delete yourself'),
            new OA\Response(response: 409, description: 'Conflict - Already deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Client already deleted')
            ]))
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        #[CurrentUser] Admin $admin,
        Client $client,
        ClientManager $manager,
    ): NoContentResponse {
        $manager->softDelete($client, $admin);
        $this->container->get('security.token_storage')->setToken(null);
        return new NoContentResponse();
    }

    #[Route('/api/clients/{id}/restore/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminRestoreClient',
        summary: 'Restore a soft-deleted client.',
        tags: ['Admin: Client'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(ref: new Model(type: ClientResponse::class))),
            new OA\Response(response: 404, description: 'Client not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function restore(Client $client, ClientManager $manager, ClientMapperInterface $mapper): ItemResponse
    {
        $responseDto = $mapper->map($manager->restore($client));
        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    #[Route('/api/clients/{id}/block/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminBlockClient',
        summary: 'Block a client account.',
        tags: ['Admin: Client'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(ref: new Model(type: ClientResponse::class))),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Client not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function block(Client $client, #[CurrentUser] Admin $admin, ClientMapperInterface $mapper, ClientManager $manager): ItemResponse
    {
        $responseDto = $mapper->map($manager->block($admin, $client));
        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    #[Route('/api/clients/{id}/unblock/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminUnblockClient',
        summary: 'Unblock a client account.',
        tags: ['Admin: Client'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(ref: new Model(type: ClientResponse::class)))
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function unblock(Client $client, ClientMapperInterface $mapper, ClientManager $manager): ItemResponse
    {
        $responseDto = $mapper->map($manager->unblock($client));
        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    #[Route('/api/clients/{id}/visit/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminRegisterVisit',
        summary: 'Register a gym visit for a client.',
        tags: ['Admin: Client'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))),
            new OA\Response(response: 400, description: 'No active membership', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string')
            ])),
            new OA\Response(response: 403, description: 'Client blocked or inactive')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function visit(Client $client, MembershipMapperInterface $mapper, ClientManager $manager): ItemResponse
    {
        $responseDto = $mapper->map($manager->visit($client));
        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/api/import/clients/', methods: ['POST'])]
    #[OA\Post(
        operationId: 'adminImportClients',
        summary: 'Import clients batch (Async).',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: CreateClientImportBatch::class))
        ),
        tags: ['Admin: Client'],
        responses: [
            new OA\Response(
                response: 202,
                description: 'Accepted - Import job queued.',
                content: new OA\JsonContent(ref: new Model(type: ClientImportResponseDTO::class))
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden')
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
