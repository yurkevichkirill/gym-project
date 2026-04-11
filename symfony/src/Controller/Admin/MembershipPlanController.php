<?php

namespace App\Controller\Admin;

use App\MembershipPlan\DTO\CreateMembershipPlanRequest;
use App\MembershipPlan\DTO\UpdateMembershipPlanRequest;
use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Mapper\MembershipPlanMapper;
use App\MembershipPlan\Service\MembershipPlanManager;
use App\Response\OkResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MembershipPlanController extends AbstractController
{
    #[Route('/api/membership/plans/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: CreateMembershipPlanRequest::class))]
    #[OA\Tag(name: "Admin: Membership Plan")]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload] CreateMembershipPlanRequest $requestDto,
        MembershipPlanManager                            $manager,
        MembershipPlanMapper                             $mapper,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->create($requestDto));

        return new OkResponse(
            data: $responseDto,
            status: 201,
        );
    }

    #[Route('/api/membership/plans/{id}/', methods: ['PATCH', 'PUT'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: UpdateMembershipPlanRequest::class))]
    #[OA\Tag(name: "Admin: Membership Plan")]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        #[MapRequestPayload] UpdateMembershipPlanRequest $requestDto,
        MembershipPlan                                   $membershipPlan,
        MembershipPlanManager                            $manager,
        MembershipPlanMapper                             $mapper,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->update($requestDto, $membershipPlan));

        return new OkResponse(
            data: $responseDto,
            status: 201,
        );
    }

    #[Route('/api/membership/plans/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Admin: Membership Plan")]
    #[IsGranted('ROLE_ADMIN')]
    public function remove(
        MembershipPlanManager $membershipPlanManager,
        MembershipPlan $membershipPlan,
    ): Response
    {
        $membershipPlanManager->remove($membershipPlan);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
