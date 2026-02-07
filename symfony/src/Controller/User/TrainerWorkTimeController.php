<?php

namespace App\Controller\User;

use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\TrainerWorkTime\Service\TrainerWorkTimeServiceInterface;
use DateTimeImmutable;
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

final class TrainerWorkTimeController extends AbstractController
{
    #[Route('api/trainers/{id}/work-time', methods: ['GET'], format: 'json')]
    #[OA\Parameter(
        name: 'date',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function get(Request $request, TrainerWorkTimeServiceInterface $trainerWorkTimeService, int $id): JsonResponse
    {
        try {
            $sortRaw = $request->query->get('sort', 'date:ASC');
            $sort = [];
            foreach (explode(',', $sortRaw) as $item) {
                [$field, $order] = explode(':',  $item);
                $sort[$field] = strtoupper($order);
            }
            $date = $request->query->get('date') ? new DateTimeImmutable($request->query->get('date')) : null;
            $trainerWorkTimes = $trainerWorkTimeService->findBy($id, $sort, $date);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if(empty($trainerWorkTimes)) {
            return $this->json(['error' => 'Trainer work time not found'], 404);
        }

        return $this->json($trainerWorkTimes, 200, [], [
            'groups' => ['public-trainer-worktime']
        ]);
    }

    #[Route('api/trainers/{id}/free-slots', methods: ['GET'], format: 'json')]
    #[OA\Parameter(
        name: 'date',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function getFreeSlots(Request $request, TrainerWorkTimeServiceInterface $trainerWorkTimeService, int $id): JsonResponse
    {
        try {
            $sortRaw = $request->query->get('sort', 'date:ASC');
            $sort = [];
            foreach (explode(',', $sortRaw) as $item) {
                [$field, $order] = explode(':',  $item);
                $sort[$field] = strtoupper($order);
            }
            $date = $request->query->get('date') ? new DateTimeImmutable($request->query->get('date')) : null;
            $trainerWorkTimes = $trainerWorkTimeService->findBy($id, $sort, $date);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if(empty($trainerWorkTimes)) {
            return $this->json(['error' => 'Trainer work time not found'], 404);
        }

        return $this->json($trainerWorkTimes, 200, [], [
            'groups' => ['public-trainer-free-slots']
        ]);
    }
}
