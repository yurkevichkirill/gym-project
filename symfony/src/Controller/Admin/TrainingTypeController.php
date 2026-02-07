<?php

namespace App\Controller\Admin;

use App\TrainingType\Entity\TrainingType;
use App\TrainingType\TrainingTypeServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class TrainingTypeController extends AbstractController
{
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    #[Route('api/training-types', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    public function getAll(TrainingTypeServiceInterface $trainingTypeService, Request $request): JsonResponse
    {
        try {
            $sortRaw = $request->query->get('sort', 'name:ASC');
            $sort = [];
            foreach (explode(',', $sortRaw) as $item) {
                [$field, $order] = explode(':',  $item);
                $sort[$field] = strtoupper($order);
            }
            $trainingTypes = $trainingTypeService->findBy($sort);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if(empty($trainingTypes)) {
            return $this->json(['error' => 'No training types found'], 404);
        }

        return $this->json($trainingTypes, 200, [], [
            'groups' => ['public-training-type']
        ]);
    }

    #[Route('api/trainings-types/{id}', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    public function get(TrainingType $trainingType): JsonResponse
    {
        return $this->json($trainingType, 200, [], [
            'groups' => ['public-training-type']
        ]);
    }
}
