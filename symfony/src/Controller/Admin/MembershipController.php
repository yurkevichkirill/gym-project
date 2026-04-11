<?php

namespace App\Controller\Admin;

use App\Client\Entity\Client;
use App\Membership\DTO\CreateMembershipRequest;
use App\Membership\DTO\GetMemberships;
use App\Membership\Entity\Membership;
use App\Membership\Mapper\MembershipMapperInterface;
use App\Membership\Query\MembershipQuery;
use App\Membership\Service\MembershipManager;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\Response\OkResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MembershipController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/memberships/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'membershipPlanId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'active')]
    #[OA\Parameter(name: 'minVisits', in: 'query', example: 10)]
    #[OA\Parameter(name: 'maxVisits', in: 'query', example: 100)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'startDate:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Membership")]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        Request $request,
        MembershipMapperInterface $mapper,
        MembershipQuery $handler,
        MembershipPlanRepository $membershipPlanRepo,
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'startDate:ASC');
        $status = $request->query->get('status');
        $membershipPlan = $membershipPlanRepo->find((int) $request->query->get('membershipPlanId'));
        $minVisits = $request->query->get('minVisits') ? (int) $request->query->get('minVisits') : null;
        $maxVisits = $request->query->get('maxVisits') ? (int) $request->query->get('maxVisits') : null;
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetMemberships(
            $sortRaw,
            $membershipPlan,
            $status,
            $minVisits,
            $maxVisits,
            $page,
            $limit,
        );

        $memberships = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($membership) => $mapper->map($membership), $memberships),
            $page,
            $limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/clients/{id}/memberships/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'membershipPlanId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'active')]
    #[OA\Parameter(name: 'minVisits', in: 'query', example: 10)]
    #[OA\Parameter(name: 'maxVisits', in: 'query', example: 100)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'startDate:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Membership")]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllByClient(
        Client $client,
        Request $request,
        MembershipMapperInterface $mapper,
        MembershipQuery $handler,
        MembershipPlanRepository $membershipPlanRepo,
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'startDate:ASC');
        $status = $request->query->get('status');
        $membershipPlan = $membershipPlanRepo->find((int) $request->query->get('membershipPlanId'));
        $minVisits = $request->query->get('minVisits') ? (int) $request->query->get('minVisits') : null;
        $maxVisits = $request->query->get('maxVisits') ? (int) $request->query->get('maxVisits') : null;
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetMemberships(
            $sortRaw,
            $membershipPlan,
            $status,
            $minVisits,
            $maxVisits,
            $page,
            $limit,
            $client,
        );

        $memberships = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($membership) => $mapper->map($membership), $memberships),
            $page,
            $limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/memberships/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Admin: Membership")]
    #[IsGranted('ROLE_ADMIN')]
    public function get(
        Membership $membership,
        MembershipMapperInterface $mapper
    ): OkResponse
    {
        return new OkResponse(
            data: $mapper->map($membership),
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/clients/{id}/membership/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: CreateMembershipRequest::class))]
    #[OA\Tag(name: "Admin: Membership")]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Client $client,
        #[MapRequestPayload] CreateMembershipRequest $requestDto,
        MembershipMapperInterface $mapper,
        MembershipManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->create($client, $requestDto->membershipPlanId));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_CREATED,
        );
    }

    #[Route('/api/memberships/{id}/freeze/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Membership")]
    #[IsGranted('ROLE_ADMIN')]
    public function freeze(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->freeze($membership));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/memberships/{id}/unfreeze/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Membership")]
    #[IsGranted('ROLE_ADMIN')]
    public function unfreeze(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->unfreeze($membership));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/memberships/{id}/renew/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Membership")]
    #[IsGranted('ROLE_ADMIN')]
    public function renew(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->renew($membership));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/memberships/{id}/terminate/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Membership")]
    #[IsGranted('ROLE_ADMIN')]
    public function terminate(
        Membership $membership,
        MembershipMapperInterface $mapper,
        MembershipManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->terminate($membership));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
