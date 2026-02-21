<?php

namespace App\Controller\All;

use App\MembershipPlan\DTO\GetMembershipPlans;
use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Mapper\MembershipPlanMapperInterface;
use App\MembershipPlan\Query\MembershipPlansQuery;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\MembershipPlan\Service\MembershipPlanServiceInterface;
use App\Response\OkResponse;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class MembershipPlanController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/membership/plans', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'minPrice', in: 'query', example: 50)]
    #[OA\Parameter(name: 'maxPrice', in: 'query', example: 100)]
    #[OA\Parameter(name: 'durationDays', in: 'query', example: 30)]
    #[OA\Parameter(name: 'sessionLimit', in: 'query', example: 8)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'durationDays:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "All: MembershipPlan")]
    public function getAll(
        Request $request,
        MembershipPlanMapperInterface $mapper,
        MembershipPlansQuery $handler,
        MembershipPlanRepository $membershipPlanRepo,
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'durationDays:ASC');
        $durationDays = $request->query->get('durationDays') ? (int) $request->query->get('durationDays') : null;
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $sessionLimit = $request->query->get('sessionLimit') ? (int) $request->query->get('sessionLimit') : null;
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetMembershipPlans($minPrice, $maxPrice, $durationDays, $sessionLimit, $sortRaw, $page, $limit);

        $plans = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($plan) => $mapper->map($plan), $plans),
            $page,
            $limit,
            $membershipPlanRepo->count(),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('api/membership/plans/{id}', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "All: MembershipPlan")]
    public function get(
        MembershipPlan $membershipPlan,
        MembershipPlanMapperInterface $mapper,
    ): OkResponse
    {
        return new OkResponse(
            data: $mapper->map($membershipPlan),
            status: Response::HTTP_OK,
        );
    }
}
