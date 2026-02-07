<?php

namespace App\Controller\Admin;

use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\MembershipPlan\Service\MembershipPlanServiceInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class MembershipPlanController extends AbstractController
{
    #[Route('api/membership-plans', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: MembershipPlan::class, groups: ['create-update-membership-plan']))]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Request $request,
        MembershipPlanRepository $repo,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $json = $request->getContent();
        try {
            $membershipPlan = $serializer->deserialize($json, MembershipPlan::class, 'json');
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

        try {
            $repo->create($membershipPlan);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($membershipPlan, 201, [], [
            'groups' => ['public-membership-plan']
        ]);
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
    public function delete(MembershipPlanRepository $repo, MembershipPlan $membershipPlan): JsonResponse
    {
        try {
            $repo->remove($membershipPlan);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
