<?php

namespace App\Controller\User;

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
    #[Route('/api/membership-plans', methods: ['GET'], format: 'json')]
    #[OA\Parameter(
        name: 'sessionLimit',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    #[IsGranted('ROLE_CLIENT')]
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
    #[IsGranted('ROLE_CLIENT')]
    public function get(MembershipPlan $membershipPlan): JsonResponse
    {
        return $this->json($membershipPlan, 200, [], [
            'groups' => ['public-membership-plan']
        ]);
    }
}
