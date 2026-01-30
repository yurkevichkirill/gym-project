<?php

namespace App\Trainer\Controller;

use App\Trainer\Entity\Trainer;
use App\TrainingType\Entity\TrainingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class TrainerController extends AbstractController
{
    #[Route('api/trainers', methods: ['GET'], format: 'json')]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $trainers = $em->getRepository(Trainer::class)->findAll();

        return $this->json($trainers, 200, [], [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn (object $obj) => $obj->getId(),
            'groups' => ['public-trainer']
        ]);
    }

    #[Route('api/trainers/{id}', methods: ['GET'], format: 'json')]
    public function show(Trainer $trainer): JsonResponse
    {
        return $this->json($trainer, 200, [], [
            'groups' => ['public-trainer']
        ]);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('api/trainers', methods: ['POST'], format: 'json')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $training_type = $em->getRepository(TrainingType::class)->find($data['trainingType']);
        if(is_null($training_type)) {
            return $this->json(['error' => 'Training type not found'], 404);
        }

        try {
            $trainer = $serializer->deserialize($request->getContent(), Trainer::class, 'json');
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
        $trainer->setTrainingType($training_type);

        $errors = $validator->validate($trainer);
        if(count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        $em->persist($trainer);
        try {
            $em->flush();
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($trainer, 201, [], [
            'groups' => ['public-trainer']
        ]);
    }

    #[Route('api/trainers/{id}', methods: ['PUT', 'PATCH'], format: 'json')]
    public function update(
        Trainer $trainer,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse
    {
        try {
            $serializer->deserialize($request->getContent(), Trainer::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $trainer
            ]);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['training_type']['id'])) {
            $trainingType = $em->getRepository(TrainingType::class)->find($data['training_type']['id']);

            if (is_null($trainingType)) {
                return $this->json(['error' => 'Training type not found'], 404);
            }

            $trainer->setTrainingType($trainingType);
        }

        $errors = $validator->validate($trainer);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        try {
            $em->flush();
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($trainer, 200, [], [
            'groups' => 'public-trainer',
        ]);
    }

    #[Route('api/trainers/{id}', methods: ['DELETE'], format: 'json')]
    public function remove(Trainer $trainer, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($trainer);
        $em->flush();

        return $this->json(null, 204);
    }
}
