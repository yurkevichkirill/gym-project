<?php

namespace App\MembershipPlan\Controller;

use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\MembershipPlan\Service\MembershipPlanServiceInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use OpenApi\Attributes as OA;

final class MembershipPlanController extends AbstractController
{
    #[Route('/api/membership-plans', methods: ['GET'], format: 'json')]
    #[OA\Parameter(
        name: 'sessionLimit',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    public function getAll(Request $request, MembershipPlanServiceInterface $membershipPlanService): JsonResponse
    {

        try {
            $sortRaw = $request->query->get('sort', 'price:ASC');
            $sort = [];
            foreach (explode(',', $sortRaw) as $item) {
                [$field, $order] = explode(':',  $item);
                $sort[$field] = strtoupper($order);
            }
            $sessionLimit = $request->query->get('sessionLimit');
            $membershipPlans = $membershipPlanService->findBy($sort, $sessionLimit);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if(empty($membershipPlans)) {
            return $this->json(['error' => 'No membership plans found'], 404);
        }

        return $this->json($membershipPlans, 200, [], [
            'groups' => 'public-membership-plan'
        ]);

    }

    #[Route('api/membership-plans/{id}', methods: ['GET'], format: 'json')]
    public function get(MembershipPlan $membershipPlan): JsonResponse
    {
        return $this->json($membershipPlan, 200, [], [
            'groups' => ['public-membership-plan']
        ]);
    }

    #[Route('api/membership-plans', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: MembershipPlan::class, groups: ['create-update-membership-plan']))]
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
