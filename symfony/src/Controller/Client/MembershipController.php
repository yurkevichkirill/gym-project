<?php

namespace App\Controller\Client;

use App\Client\Entity\Client;
use App\Membership\DTO\CreateMembershipRequest;
use App\Membership\Entity\Membership;
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
    #[OA\Parameter(name: 'membershipPlanId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'active')]
    #[OA\Parameter(name: 'minVisits', in: 'query', example: 10)]
    #[OA\Parameter(name: 'maxVisits', in: 'query', example: 100)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'startDate:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Client: Membership")]
    #[IsGranted('ROLE_CLIENT')]
    public function getAll(
        #[CurrentUser] Client $client,
        Request $request,
        MembershipMapperInterface $mapper,
        MembershipQuery $handler,
        GetMembershipsFactory $factory,
    ): CollectionResponse
    {
        $queryDto = $factory->fromRequest(
            request: $request,
            client: $client,
        );

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

    #[Route('/api/me/memberships/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Client: Membership")]
    public function get(
        Membership $membership,
        MembershipMapperInterface $mapper
    ): ItemResponse
    {
        $this->denyAccessUnlessGranted("MEMBERSHIP_VIEW", $membership);

        return new ItemResponse(
            data: $mapper->map($membership),
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/me/membership/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: CreateMembershipRequest::class))]
    #[OA\Tag(name: "Client: Membership")]
    #[IsGranted('ROLE_CLIENT')]
    public function create(
        #[CurrentUser] Client $client,
        #[MapRequestPayload] CreateMembershipRequest $requestDto,
        MembershipMapperInterface $mapper,
        MembershipManager $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->create($client, $requestDto->membershipPlanId));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/me/memberships/{id}/freeze/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Client: Membership")]
    public function freeze(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager,
    ): ItemResponse
    {
        $this->denyAccessUnlessGranted("MEMBERSHIP_EDIT", $membership);

        $responseDto = $mapper->map($manager->freeze($membership));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/me/memberships/{id}/unfreeze/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Client: Membership")]
    public function unfreeze(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager,
    ): ItemResponse
    {
        $this->denyAccessUnlessGranted("MEMBERSHIP_EDIT", $membership);

        $responseDto = $mapper->map($manager->unfreeze($membership));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws Throwable
     */
    #[Route('/api/me/memberships/{id}/renew/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Client: Membership")]
    public function renew(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager,
    ): ItemResponse
    {
        $this->denyAccessUnlessGranted("MEMBERSHIP_EDIT", $membership);

        $responseDto = $mapper->map($manager->renew($membership));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    #[Route('/api/me/memberships/{id}/terminate/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Client: Membership")]
    #[IsGranted('ROLE_CLIENT')]
    public function terminate(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager,
    ): ItemResponse
    {
        $this->denyAccessUnlessGranted("MEMBERSHIP_EDIT", $membership);

        $responseDto = $mapper->map($manager->terminate($membership));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
