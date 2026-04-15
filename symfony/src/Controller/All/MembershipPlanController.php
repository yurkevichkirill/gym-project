<?php

namespace App\Controller\All;

use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Factory\GetMembershipPlansFactory;
use App\MembershipPlan\Mapper\MembershipPlanMapperInterface;
use App\MembershipPlan\Query\MembershipPlansQuery;
use App\Response\OkResponse;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class MembershipPlanController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/membership/plans/', methods: ['GET'], format: 'json')]
    #[Cache(public: true)]
    #[OA\Parameter(name: 'minPrice', in: 'query', example: 50)]
    #[OA\Parameter(name: 'maxPrice', in: 'query', example: 100)]
    #[OA\Parameter(name: 'minDurationDays', in: 'query', example: 30)]
    #[OA\Parameter(name: 'maxDurationDays', in: 'query', example: 30)]
    #[OA\Parameter(name: 'minSessionLimit', in: 'query', example: 8)]
    #[OA\Parameter(name: 'maxSessionLimit', in: 'query', example: 8)]
    #[OA\Parameter(name: 'isUnlimited', in: 'query', example: 'true')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'durationDays:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "All: MembershipPlan")]
    public function getAll(
        Request $request,
        MembershipPlanMapperInterface $mapper,
        MembershipPlansQuery $handler,
        GetMembershipPlansFactory $factory,
    ): OkResponse
    {
        $queryDto = $factory->fromRequest($request);

        $plans = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($plan) => $mapper->map($plan), $plans),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/membership/plans/{id}/', methods: ['GET'], format: 'json')]
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
