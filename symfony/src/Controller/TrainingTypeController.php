<?php

namespace App\Controller;

use App\Entity\TrainingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class TrainingTypeController extends AbstractController
{
    #[Route('api/gym', methods: ['GET'], format: 'json')]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $training_types = $em->getRepository(TrainingType::class)->findAll();

        return $this->json($training_types, 200, [], [
            'groups' => ['public-training-type']
        ]);
    }

    #[Route('api/gym/{id}', methods: ['GET'], format: 'json')]
    public function show(TrainingType $trainingType): JsonResponse
    {
        return $this->json($trainingType, 200, [], [
            'groups' => ['public-training-type']
        ]);
    }

    #[Route('api/gym', methods: ['POST'], format: 'json')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $json = $request->getContent();
        try {
            $trainingType = $serializer->deserialize($json, TrainingType::class, 'json');
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($trainingType);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        $em->persist($trainingType);
        try {
            $em->flush();
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($trainingType, 201, [], [
            'groups' => ['public-training-type']
        ]);
    }

    #[Route('api/gym/{id}', methods: ['PATCH', 'PUT'], format: 'json')]
    public function update(
        TrainingType $trainingType,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        try {
            $serializer->deserialize($request->getContent(), TrainingType::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $trainingType
            ]);
            $em->flush();
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($trainingType);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        return $this->json($trainingType, 200, [], [
            'groups' => ['public-training-type']
        ]);
    }

    #[Route('api/gym/{id}', methods: ['DELETE'], format: 'json')]
    public function delete(EntityManagerInterface $em, TrainingType $trainingType): JsonResponse
    {
        $em->remove($trainingType);
        $em->flush();

        return $this->json(null, 204);
    }
}
