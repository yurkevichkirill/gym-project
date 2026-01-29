<?php

namespace App\Controller;

use App\Entity\Trainer;
use App\Entity\Training;
use App\Enum\DayOfWeekEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class TrainingController extends AbstractController
{
    #[Route('api/trainers/{id}/trainings', methods: ['GET'], format: 'json')]
    public function index(EntityManagerInterface $em, int $id): JsonResponse
    {
        $trainer = $em->getRepository(Trainer::class)->find($id);

        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        $trainings = $em->getRepository(Training::class)->findBy(['trainer' => $trainer]);

        if(empty($trainings)) {
            return $this->json(['error' => 'No trainings found'], 404);
        }

        return $this->json($trainings, 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-training']
        ]);
    }

    #[Route('api/trainers/{id}/trainings/{day_of_week}', methods: ['GET'], format: 'json')]
    public function show(EntityManagerInterface $em, int $id, DayOfWeekEnum $day_of_week): JsonResponse
    {
        $trainer = $em->getRepository(Trainer::class)->find($id);

        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        $training = $em->getRepository(Training::class)->findBy([
            'trainer' => $trainer,
            'day_of_week' => $day_of_week
        ]);

        if(empty($training)) {
            return $this->json(['text' => 'Trainer has no trainings in this day'], 200);
        }

        return $this->json($training[0], 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-training']
        ]);
    }

    #[Route('api/trainers/{id}/trainings', methods: ['POST'], format: 'json')]
    public function create(
        int $id,
        EntityManagerInterface $em,
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

        $trainer = $em->getRepository(Trainer::class)->find($id);
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

        $em->persist($training);
        try {
            $em->flush();
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($training, 201, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-training']
        ]);
    }

    #[Route('api/trainers/{id}/trainings/{day_of_week}', methods: ['PUT', 'PATCH'], format: 'json')]
    public function update(
        int $id,
        DayOfWeekEnum $day_of_week,
        EntityManagerInterface $em,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $trainer = $em->getRepository(Trainer::class)->find($id);
        if(is_null($trainer)) {
            return $this->json(['error' => "Trainer not found"], 404);
        }

        $training = $em->getRepository(Training::class)->findBy([
            'trainer' => $trainer,
            'day_of_week' => $day_of_week
        ]);
        if(empty($training)) {
            return $this->json(['text' => 'Trainer has no trainings in this day'], 200);
        }

        try {
            $serializer->deserialize($request->getContent(), Training::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $training[0]
            ]);
            $em->flush();
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

        return $this->json($training[0], 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-training']
        ]);
    }

    #[Route('api/trainers/{id}/trainings/{day_of_week}', methods: ['DELETE'], format: 'json')]
    public function delete(int $id, DayOfWeekEnum $day_of_week, EntityManagerInterface $em): JsonResponse
    {
        $trainer = $em->getRepository(Trainer::class)->find($id);
        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        $training = $em->getRepository(Training::class)->findBy([
            'trainer' => $trainer,
            'day_of_week' => $day_of_week
        ]);
        if(empty($training)) {
            return $this->json(['error' => 'Trainer has no trainings in this day'], 200);
        }

        $em->remove($training[0]);
        $em->flush();
        return $this->json(null, 204);
    }
}
