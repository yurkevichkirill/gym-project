<?php

namespace App\TrainerWorkTime\Controller;

use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Trainer\Repository\TrainerRepository;
use App\Enum\DayOfWeekEnum;
use App\Trainer\Entity\Trainer;
use App\Trainer\Service\TrainerServiceInterface;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Service\TrainerWorkTimeServiceInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use OpenApi\Attributes as OA;

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

    #[Route('api/trainers/{id}/work-time', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainerWorkTime::class, groups: ['create-update-trainer-worktime']))]
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
            'groups' => ['public-trainer-worktime']
        ]);
    }

    #change route
    #[Route('api/work-time/{id}', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainerWorkTime::class, groups: ['create-update-trainer-worktime']))]
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
            'groups' => ['public-trainer-worktime']
        ]);
    }

    #change route
    #[Route('api/work-time/{id}', methods: ['DELETE'], format: 'json')]
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
