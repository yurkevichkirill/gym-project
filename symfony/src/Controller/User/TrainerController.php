<?php

namespace App\Controller\User;

use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\Trainer\Service\TrainerServiceInterface;
use App\TrainingType\Repository\TrainingTypeRepository;
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

final class TrainerController extends AbstractController
{
    #[Route('api/trainers', methods: ['GET'], format: 'json')]
    #[OA\Parameter(
        name: 'trainingTypeId',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function getAll(Request $request, TrainerServiceInterface $trainerService): JsonResponse
    {
        try {
            $sortRaw = $request->query->get('sort', 'price:ASC');
            $sort = [];
            foreach (explode(',', $sortRaw) as $item) {
                [$field, $order] = explode(':',  $item);
                $sort[$field] = strtoupper($order);
            }
            $trainingTypeId = $request->query->get('trainingTypeId');
            $trainers = $trainerService->findBy($sort, $trainingTypeId);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if(empty($trainers)) {
            return $this->json(['error' => 'No trainers found'], 404);
        }

        return $this->json($trainers, 200, [], [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn (object $obj) => $obj->getId(),
            'groups' => ['public-trainer']
        ]);
    }

    #[Route('api/trainers/{id}', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    public function get(Trainer $trainer): JsonResponse
    {
        return $this->json($trainer, 200, [], [
            'groups' => ['public-trainer']
        ]);
    }
}
