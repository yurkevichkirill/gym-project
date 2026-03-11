<?php

namespace App\Controller\Admin;

use App\MembershipPlan\DTO\CreateMembershipPlanRequest;
use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Mapper\MembershipPlanMapper;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\MembershipPlan\Service\MembershipPlanManager;
use App\MembershipPlan\Service\MembershipPlanServiceInterface;
use App\Response\OkResponse;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class MembershipPlanController extends AbstractController
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('api/membership/plans', methods: ['POST'], format: 'json')]
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
            data: $requestDto,
            status: 201,
        );
    }

    #[Route('api/membership-plans/{id}', methods: ['PATCH', 'PUT'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: MembershipPlan::class, groups: ['create-update-membership-plan']))]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        MembershipPlan $membershipPlan,
        Request $request,
        SerializerInterface $serializer,
        MembershipPlanRepository $repo,
        ValidatorInterface $validator
    ): JsonResponse
    {
        try {
            $serializer->deserialize($request->getContent(), MembershipPlan::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $membershipPlan
            ]);
            $repo->save();
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($membershipPlan);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        return $this->json($membershipPlan, 200, [], [
            'groups' => ['public-membership-plan']
        ]);
    }

    #[Route('api/membership-plans/{id}', methods: ['DELETE'], format: 'json')]
    #[IsGranted('ROLE_ADMIN')]
    public function remove(MembershipPlanRepository $repo, MembershipPlan $membershipPlan): JsonResponse
    {
        try {
            $repo->remove($membershipPlan);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
