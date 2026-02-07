<?php

namespace App\Booking\Controller;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Repository\BookingRepository;
use App\Booking\Service\BookingServiceInterface;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Enum\DayOfWeekEnum;
use App\Training\Entity\Training;
use App\Training\Repository\TrainingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use OpenApi\Attributes as OA;

final class BookingController extends AbstractController
{
    #[Route('/api/clients/{id}/bookings', methods: ['GET'], format: 'json')]
    #[OA\Parameter(
        name: 'status',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function getAll(int $id, BookingServiceInterface $bookingService, Request $request): JsonResponse
    {
        try {
            $sortRaw = $request->query->get('sort', 'bookedAt:ASC');
            $sort = [];
            foreach (explode(',', $sortRaw) as $item) {
                [$field, $order] = explode(':',  $item);
                $sort[$field] = strtoupper($order);
            }
            $status = BookingStatusEnum::tryFrom($request->query->get('status'));
            $bookings = $bookingService->findBy($id, $sort, $status);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if(empty($bookings)) {
            return $this->json(['error' => 'No bookings found'], 404);
        }

        return $this->json($bookings, 200, [], [
            'groups' => 'public-booking',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }

    #[Route('api/clients/{clientId}/bookings/{bookingId}', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    public function get(int $clientId, int $bookingId, BookingRepository $bookingRepo, ClientRepository $clientRepo): JsonResponse
    {
        $client = $clientRepo->find($clientId);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $bookings = $bookingRepo->findBy([
            "client" => $client,
            "id" => $bookingId
        ]);
        if(empty($bookings)) {
            return $this->json(['error' => "Client has no bookings"], 404);
        }

        return $this->json($bookings[0], 200, [], [
            'groups' => 'public-booking',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }

    #[Route('api/clients/{id}/bookings', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Booking::class, groups: ['create-booking']))]
    public function create(
        int $id,
        Request $request,
        BookingRepository $bookingRepo,
        ClientRepository $clientRepo,
        TrainingRepository $trainingRepo,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $training = $trainingRepo->find($data['training']['id']);
        if(is_null($training)) {
            return $this->json(['error' => 'Training not found'], 404);
        }

        $client = $clientRepo->find($id);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        try {
            $booking = $serializer->deserialize($request->getContent(), Booking::class, 'json');
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
        $booking->setClient($client);
        $booking->setTraining($training);

        $errors = $validator->validate($booking);
        if(count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        try {
            $bookingRepo->create($booking);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($booking, 201, [], [
            'groups' => 'public-booking',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }

    #[Route('api/clients/{clientId}/bookings/{id}', methods: ['PUT', 'PATCH'], format: 'json')]
    public function update(
        int $clientId,
        Booking $booking,
        Request $request,
        ValidatorInterface $validator,
        BookingRepository $bookingRepo,
        TrainingRepository $trainingRepo,
        ClientRepository $clientRepo,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $client = $clientRepo->find($clientId);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        try {
            $serializer->deserialize($request->getContent(), Booking::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $booking
            ]);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['training']['id'])) {
            $training = $trainingRepo->find($data['training']['id']);

            if (is_null($training)) {
                return $this->json(['error' => 'Client not found'], 404);
            }

            $booking->setTraining($training);
        }

        $errors = $validator->validate($booking);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        try {
            $bookingRepo->save();
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($booking, 200, [], [
            'groups' => 'public-booking',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }

    #[Route('api/clients/{clientId}/bookings/{id}', methods: ['DELETE'], format: 'json')]
    public function remove(
        int $clientId,
        Booking $booking,
        BookingRepository $bookingRepo,
        ClientRepository $clientRepo
    ): JsonResponse
    {
        $client = $clientRepo->find($clientId);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        try {
            $bookingRepo->remove($booking);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
