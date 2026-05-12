<?php

namespace App\Controller\Client;

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
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class MembershipController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/me/memberships/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getClientMemberships',
        summary: 'Get a list of current client memberships.',
        tags: ['Client: Membership'],
        parameters: [
            new OA\Parameter(name: 'membershipPlanId', in: 'query', schema: new OA\Schema(type: 'integer'), example: 6),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: MembershipStatusEnum::class), example: 'active'),
            new OA\Parameter(name: 'minVisits', in: 'query', schema: new OA\Schema(type: 'integer'), example: 10),
            new OA\Parameter(name: 'maxVisits', in: 'query', schema: new OA\Schema(type: 'integer'), example: 100),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'startDate:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
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
    #[IsGranted('ROLE_CLIENT')]
    public function getAll(
        #[CurrentUser] Client $client,
        Request $request,
        MembershipMapperInterface $mapper,
        MembershipQuery $handler,
        GetMembershipsFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request, $client);
        $memberships = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn($membership) => $mapper->map($membership), $memberships),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/me/memberships/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getMembershipById',
        summary: 'Get details of a specific membership.',
        tags: ['Client: Membership'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership details',
                content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))
            ),
            new OA\Response(response: 403, description: 'Access Denied'),
            new OA\Response(response: 404, description: 'Membership not found')
        ]
    )]
    public function get(Membership $membership, MembershipMapperInterface $mapper): ItemResponse
    {
        $this->denyAccessUnlessGranted("MEMBERSHIP_VIEW", $membership);

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
        summary: 'Purchase a new membership plan.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: CreateMembershipRequest::class))
        ),
        tags: ['Client: Membership'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Membership created successfully.',
                content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Already has active membership (MembershipActiveException).',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Client already has an active membership'),
                        new OA\Property(property: 'request_id', type: 'string', nullable: true)
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden - User is blocked or inactive'),
            new OA\Response(response: 404, description: 'Membership plan not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function create(
        #[CurrentUser] Client $client,
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
    #[Route('/api/me/memberships/{id}/freeze/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'freezeMembership',
        summary: 'Freeze an active membership.',
        tags: ['Client: Membership'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership frozen successfully.',
                content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Membership is not ACTIVE.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(response: 403, description: 'Access Denied'),
            new OA\Response(response: 404, description: 'Membership not found')
        ]
    )]
    public function freeze(Membership $membership, MembershipMapperInterface $mapper, MembershipManager $manager): ItemResponse
    {
        $this->denyAccessUnlessGranted("MEMBERSHIP_EDIT", $membership);
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
        operationId: 'unfreezeMembership',
        summary: 'Unfreeze a frozen membership.',
        tags: ['Client: Membership'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership unfrozen successfully.',
                content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Membership is not FROZEN.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(response: 403, description: 'Access Denied'),
            new OA\Response(response: 404, description: 'Membership not found')
        ]
    )]
    public function unfreeze(Membership $membership, MembershipMapperInterface $mapper, MembershipManager $manager): ItemResponse
    {
        $this->denyAccessUnlessGranted("MEMBERSHIP_EDIT", $membership);
        $responseDto = $mapper->map($manager->unfreeze($membership));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws Throwable
     */
    #[Route('/api/me/memberships/{id}/renew/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'renewMembership',
        summary: 'Renew a membership based on its plan.',
        tags: ['Client: Membership'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership renewed successfully (new membership created).',
                content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))
            ),
            new OA\Response(response: 403, description: 'Access Denied'),
            new OA\Response(response: 404, description: 'Membership or Plan not found')
        ]
    )]
    public function renew(Membership $membership, MembershipMapperInterface $mapper, MembershipManager $manager): ItemResponse
    {
        $this->denyAccessUnlessGranted("MEMBERSHIP_EDIT", $membership);
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
        operationId: 'terminateMembership',
        summary: 'Terminate a membership (sets status to EXPIRED and end date to now).',
        tags: ['Client: Membership'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership terminated successfully.',
                content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Membership already EXPIRED.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(response: 403, description: 'Access Denied'),
            new OA\Response(response: 404, description: 'Membership not found')
        ]
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function terminate(Membership $membership, MembershipMapperInterface $mapper, MembershipManager $manager): ItemResponse
    {
        $this->denyAccessUnlessGranted("MEMBERSHIP_EDIT", $membership);
        $responseDto = $mapper->map($manager->terminate($membership));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }
}
