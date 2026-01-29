<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Client;
use App\Entity\Training;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class BookingController extends AbstractController
{
    #[Route('/api/clients/{id}/bookings', methods: ['GET'], format: 'json')]
    public function index(int $id, EntityManagerInterface $em): JsonResponse
    {
        $client = $em->getRepository(Client::class)->find($id);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $bookings = $em->getRepository(Booking::class)->findBy([
            "client" => $client
        ]);
        if(empty($bookings)) {
            return $this->json(['error' => "Client has no bookings"], 404);
        }

        return $this->json($bookings, 200, [], [
            'groups' => 'public-booking',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }

    #[Route('api/clients/{clientId}/bookings/{bookingId}', methods: ['GET'], format: 'json')]
    public function show(int $clientId, int $bookingId, EntityManagerInterface $em): JsonResponse
    {
        $client = $em->getRepository(Client::class)->find($clientId);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $bookings = $em->getRepository(Booking::class)->findBy([
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
    public function create(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $training = $em->getRepository(Training::class)->find($data['training']['id']);
        if(is_null($training)) {
            return $this->json(['error' => 'Training not found'], 404);
        }

        $client = $em->getRepository(Client::class)->find($id);
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

        $em->persist($booking);
        try {
            $em->flush();
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
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $client = $em->getRepository(Client::class)->find($clientId);
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
            $training = $em->getRepository(Training::class)->find($data['training']['id']);

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
            $em->flush();
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
    public function remove(int $clientId, Booking $booking, EntityManagerInterface $em): JsonResponse
    {
        $client = $em->getRepository(Client::class)->find($clientId);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $em->remove($booking);
        $em->flush();

        return $this->json(null, 204);
    }
}
