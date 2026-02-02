<?php

namespace App\TrainerWorkTime\Controller;

use App\Client\Entity\Client;
use App\Trainer\Repository\TrainerRepository;
use App\Enum\DayOfWeekEnum;
use App\Trainer\Entity\Trainer;
use App\Trainer\Service\TrainerServiceInterface;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use OpenApi\Attributes as OA;

final class TrainerWorkTimeController extends AbstractController
{
    #[Route('api/trainers/{id}/availabilities', methods: ['GET'], format: 'json')]
    public function getAll(TrainerWorkTimeRepository $availabilityRepo, TrainerRepository $trainerRepo, int $id): JsonResponse
    {
        $trainer = $trainerRepo->find($id);

        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        $trainerAvailabilities = $availabilityRepo->findBy(['trainer' => $trainer]);

        if(empty($trainerAvailabilities)) {
            return $this->json(['error' => 'No abilities found'], 404);
        }

        return $this->json($trainerAvailabilities, 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-trainer-availability']
        ]);
    }

    #[Route('api/trainers/{id}/availabilities/{day_of_week}', methods: ['GET'], format: 'json')]
    public function get(TrainerWorkTimeRepository $availabilityRepo, TrainerRepository $trainerRepo, int $id, DayOfWeekEnum $day_of_week): JsonResponse
    {
        $trainer = $trainerRepo->find($id);

        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        $trainerAvailability = $availabilityRepo->findBy([
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
    public function getAvailable(
        TrainerRepository $trainerRepo,
        int $id,
        DayOfWeekEnum $day_of_week,
        TrainerServiceInterface $trainerService
    ): JsonResponse
    {
        $trainer = $trainerRepo->find($id);

        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        try {
            $available = $trainerService->getAvailable($trainer, $day_of_week);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($available, 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-trainer-availability']
        ]);
    }

    #[Route('api/trainers/{id}/availabilities', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainerWorkTime::class, groups: ['create-update-trainer-availability']))]
    public function create(
        int $id,
        TrainerWorkTimeRepository $availabilityRepo,
        TrainerRepository $trainerRepo,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        try {
            $trainerAvailability = $serializer->deserialize($request->getContent(), TrainerWorkTime::class, 'json');
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $trainer = $trainerRepo->find($id);
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

        try {
            $availabilityRepo->create($trainerAvailability);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($trainerAvailability, 201, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-trainer-availability']
        ]);
    }

    #[Route('api/trainers/{id}/availabilities/{dayOfWeek}', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainerWorkTime::class, groups: ['create-update-trainer-availability']))]
    public function update(
        int $id,
        DayOfWeekEnum $dayOfWeek,
        TrainerWorkTimeRepository $availabilityRepo,
        TrainerRepository $trainerRepo,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $trainer = $trainerRepo->find($id);
        if(is_null($trainer)) {
            return $this->json(['error' => "Trainer not found"], 404);
        }

        $trainerAvailability = $availabilityRepo->findBy([
            'trainer' => $trainer,
            'dayOfWeek' => $dayOfWeek
            ]);
        if(empty($trainerAvailability)) {
            return $this->json(['text' => 'Trainer don\'t work in this day'], 200);
        }

        try {
            $serializer->deserialize($request->getContent(), TrainerWorkTime::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $trainerAvailability[0]
            ]);
            $availabilityRepo->save();
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
    public function delete(
        int $id,
        DayOfWeekEnum $day_of_week,
        TrainerWorkTimeRepository $availabilityRepo,
        TrainerRepository $trainerRepo
    ): JsonResponse
    {
        $trainer = $trainerRepo->find($id);
        if(is_null($trainer)) {
            return $this->json(['error' => 'Trainer not found'], 404);
        }

        $trainerAvailability = $availabilityRepo->findBy([
            'trainer' => $trainer,
            'day_of_week' => $day_of_week
        ]);
        if(empty($trainerAvailability)) {
            return $this->json(['error' => 'Trainer don\'t work in this day'], 200);
        }

        try {
            $availabilityRepo->remove($trainerAvailability[0]);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
