<?php

namespace App\Training\Controller;

use App\Enum\DayOfWeekEnum;
use App\Trainer\Repository\TrainerRepository;
use App\Training\Entity\Training;
use App\Training\Repository\TrainingRepository;
use App\Training\Service\TrainingServiceInterface;
use DateTimeImmutable;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use OpenApi\Attributes as OA;

//change endpoints
final class TrainingController extends AbstractController
{
    #[Route('api/trainers/{id}/trainings', methods: ['GET'], format: 'json')]
    #[OA\Parameter(
        name: 'date',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    public function getAll(int $id, Request $request, TrainingServiceInterface $trainingService): JsonResponse
    {
        try {
            $sortRaw = $request->query->get('sort', 'startTime:ASC');
            $sort = [];
            foreach (explode(',', $sortRaw) as $item) {
                [$field, $order] = explode(':',  $item);
                $sort[$field] = strtoupper($order);
            }
            $date = $request->query->get('date') ? new DateTimeImmutable($request->query->get('date')) : null;
            $trainings = $trainingService->findBy($id, $sort, $date);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if(empty($trainings)) {
            return $this->json(['error' => 'No trainings found'], 404);
        }

        return $this->json($trainings, 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-training']
        ]);
    }

    #[Route('api/trainings/{id}', methods: ['GET'], format: 'json')]
    public function get(
        TrainingRepository $trainingRepo,
        int $id
    ): JsonResponse
    {
        $training = $trainingRepo->find($id);
        if(empty($training)) {
            return $this->json(['error' => 'Training not found'], 404);
        }

        return $this->json($training, 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-training']
        ]);
    }

    #[Route('api/trainers/{id}/trainings', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Training::class, groups: ['create-update-training']))]
    public function create(
        int $id,
        TrainingRepository $trainingRepo,
        TrainerRepository $trainerRepo,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        try {
            $training = $serializer->deserialize($request->getContent(), Training::class, 'json');
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $trainer = $trainerRepo->find($id);
        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        $training->setTrainer($trainer);

        $errors = $validator->validate($training);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        try {
            $trainingRepo->create($training);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($training, 201, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-training']
        ]);
    }

    #[Route('api/trainings/{id}', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Training::class, groups: ['create-update-training']))]
    public function update(
        int $id,
        TrainingRepository $trainingRepo,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $training = $trainingRepo->find($id);
        if(empty($training)) {
            return $this->json(['error' => 'Training not found'], 404);
        }

        try {
            $serializer->deserialize($request->getContent(), Training::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $training
            ]);
            $trainingRepo->save();
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($training[0]);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        return $this->json($training, 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-training']
        ]);
    }

    #[Route('api/trainings/{id}', methods: ['DELETE'], format: 'json')]
    public function delete(
        int $id,
        TrainingRepository $trainingRepo,
    ): JsonResponse
    {
        $training = $trainingRepo->find($id);
        if(empty($training)) {
            return $this->json(['error' => 'Training not found'], 404);
        }

        try {
            $trainingRepo->remove($training);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
