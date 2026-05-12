<?php

namespace App\Controller\Admin;

use App\Client\Entity\Client;
use App\Membership\DTO\CreateMembershipRequest;
use App\Membership\DTO\MembershipResponse;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Factory\GetMembershipsFactory;
use App\Membership\Mapper\MembershipMapperInterface;
use App\Membership\Query\MembershipQuery;
use App\Membership\Service\MembershipManager;
use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use DateMalformedStringException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class MembershipController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/memberships/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetMemberships',
        summary: 'Get all memberships (Admin).',
        tags: ['Admin: Membership'],
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
                description: 'List of memberships',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: MembershipResponse::class))),
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
        Request $request,
        MembershipMapperInterface $mapper,
        MembershipQuery $handler,
        GetMembershipsFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request);
        $memberships = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($membership) => $mapper->map($membership), $memberships),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/clients/{id}/memberships/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetClientMemberships',
        summary: 'Get memberships for a specific client.',
        tags: ['Admin: Membership'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: MembershipStatusEnum::class)),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client memberships',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: MembershipResponse::class)))
                ])
            ),
            new OA\Response(response: 404, description: 'Client not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllByClient(
        Client $client,
        Request $request,
        MembershipMapperInterface $mapper,
        MembershipQuery $handler,
        GetMembershipsFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request, $client);
        $memberships = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($membership) => $mapper->map($membership), $memberships),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/memberships/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetMembershipById',
        summary: 'Get membership details (Admin).',
        tags: ['Admin: Membership'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))),
            new OA\Response(response: 404, description: 'Membership not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function get(Membership $membership, MembershipMapperInterface $mapper): ItemResponse
    {
        return new ItemResponse(data: $mapper->map($membership), status: Response::HTTP_OK);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/clients/{id}/membership/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCreateMembership',
        summary: 'Create membership for client (Admin).',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: CreateMembershipRequest::class))),
        tags: ['Admin: Membership'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))),
            new OA\Response(response: 400, description: 'Bad Request - e.g. Already has active membership', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'request_id', type: 'string', nullable: true)
            ])),
            new OA\Response(response: 404, description: 'Client or Plan not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Client $client,
        #[MapRequestPayload] CreateMembershipRequest $requestDto,
        MembershipMapperInterface $mapper,
        MembershipManager $manager,
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
        summary: 'Freeze membership (Admin).',
        tags: ['Admin: Membership'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Frozen', content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))),
            new OA\Response(response: 400, description: 'Invalid status for freeze'),
            new OA\Response(response: 404, description: 'Membership not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function freeze(Membership $membership, MembershipMapperInterface $mapper, MembershipManager $manager): ItemResponse
    {
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
        summary: 'Unfreeze membership (Admin).',
        tags: ['Admin: Membership'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Unfrozen', content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))),
            new OA\Response(response: 400, description: 'Invalid status for unfreeze')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function unfreeze(Membership $membership, MembershipMapperInterface $mapper, MembershipManager $manager): ItemResponse
    {
        $responseDto = $mapper->map($manager->unfreeze($membership));
        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws Throwable
     */
    #[Route('/api/memberships/{id}/renew/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminRenewMembership',
        summary: 'Renew membership (Admin).',
        tags: ['Admin: Membership'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Renewed', content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class)))
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function renew(Membership $membership, MembershipMapperInterface $mapper, MembershipManager $manager): ItemResponse
    {
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
        summary: 'Terminate membership (Admin).',
        tags: ['Admin: Membership'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Terminated', content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))),
            new OA\Response(response: 400, description: 'Already expired')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function terminate(Membership $membership, MembershipMapperInterface $mapper, MembershipManager $manager): ItemResponse
    {
        $responseDto = $mapper->map($manager->terminate($membership));
        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }
}
