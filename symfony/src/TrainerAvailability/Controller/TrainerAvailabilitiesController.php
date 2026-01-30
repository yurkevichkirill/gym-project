<?php

namespace App\TrainerAvailability\Controller;

use App\TrainerAvailability\Entity\TrainerAvailability;
use App\Enum\DayOfWeekEnum;
use App\Trainer\Entity\Trainer;
use App\Trainer\Service\TrainerServiceInterface;
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

final class TrainerAvailabilitiesController extends AbstractController
{
    #[Route('api/trainers/{id}/availabilities', methods: ['GET'], format: 'json')]
    public function get(EntityManagerInterface $em, int $id): JsonResponse
    {
        $trainer = $em->getRepository(Trainer::class)->find($id);

        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        $trainerAvailabilities = $em->getRepository(TrainerAvailability::class)->findBy(['trainer' => $trainer]);

        if(empty($trainerAvailabilities)) {
            return $this->json(['error' => 'No abilities found'], 404);
        }

        return $this->json($trainerAvailabilities, 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-trainer-availability']
        ]);
    }

    #[Route('api/trainers/{id}/availabilities/{day_of_week}', methods: ['GET'], format: 'json')]
    public function show(EntityManagerInterface $em, int $id, DayOfWeekEnum $day_of_week): JsonResponse
    {
        $trainer = $em->getRepository(Trainer::class)->find($id);

        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        $trainerAvailability = $em->getRepository(TrainerAvailability::class)->findBy([
            'trainer' => $trainer,
            'day_of_week' => $day_of_week
        ]);

        if(empty($trainerAvailability)) {
            return $this->json(['text' => 'Trainer don\'t work in this day'], 200);
        }

        return $this->json($trainerAvailability[0], 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-trainer-availability']
        ]);
    }

    #[Route('api/trainers/{id}/free-slots/{day_of_week}', methods: ['GET'], format: 'json')]
    public function indexFree(
        EntityManagerInterface $em,
        int $id,
        DayOfWeekEnum $day_of_week,
        TrainerServiceInterface $trainerService
    ): JsonResponse
    {
        $trainer = $em->getRepository(Trainer::class)->find($id);

        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        try {
            $available = $trainerService->getAvailable($trainer, $day_of_week, $em);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($available, 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-trainer-availability']
        ]);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('api/trainers/{id}/availabilities', methods: ['POST'], format: 'json')]
    public function create(
        int $id,
        EntityManagerInterface $em,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        try {
            $trainerAvailability = $serializer->deserialize($request->getContent(), TrainerAvailability::class, 'json');
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $trainer = $em->getRepository(Trainer::class)->find($id);
        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        $trainerAvailability->setTrainer($trainer);

        $errors = $validator->validate($trainerAvailability);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        $em->persist($trainerAvailability);
        try {
            $em->flush();
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($trainerAvailability, 201, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-trainer-availability']
        ]);
    }

    #[Route('api/trainers/{id}/availabilities/{day_of_week}', methods: ['PUT', 'PATCH'], format: 'json')]
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

        $trainerAvailability = $em->getRepository(TrainerAvailability::class)->findBy([
            'trainer' => $trainer,
            'day_of_week' => $day_of_week
            ]);
        if(empty($trainerAvailability)) {
            return $this->json(['text' => 'Trainer don\'t work in this day'], 200);
        }

        try {
            $serializer->deserialize($request->getContent(), TrainerAvailability::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $trainerAvailability[0]
            ]);
            $em->flush();
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($trainerAvailability[0]);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        return $this->json($trainerAvailability[0], 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-trainer-availability']
        ]);
    }

    #[Route('api/trainers/{id}/availabilities/{day_of_week}', methods: ['DELETE'], format: 'json')]
    public function delete(int $id, DayOfWeekEnum $day_of_week, EntityManagerInterface $em): JsonResponse
    {
        $trainer = $em->getRepository(Trainer::class)->find($id);
        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        $trainerAvailability = $em->getRepository(TrainerAvailability::class)->findBy([
            'trainer' => $trainer,
            'day_of_week' => $day_of_week
        ]);
        if(empty($trainerAvailability)) {
            return $this->json(['error' => 'Trainer don\'t work in this day'], 200);
        }

        $em->remove($trainerAvailability[0]);
        $em->flush();
        return $this->json(null, 204);
    }
}
