<?php

namespace App\Controller\User;

use App\Booking\DTO\BookingRequest;
use App\Booking\DTO\GetClientBookings;
use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Query\ClientBookingsQuery;
use App\Booking\Repository\BookingRepository;
use App\Booking\Service\BookingManager;
use App\Booking\Service\BookingServiceInterface;
use App\Client\Entity\Client;
use App\Training\Repository\TrainingRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class BookingController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/me/bookings', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'status', in: 'query', example: 'scheduled')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'bookedAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[IsGranted('ROLE_CLIENT')]
    public function getAll(#[CurrentUser] ?Client $client, ClientBookingsQuery $handler, BookingServiceInterface $bookingService, Request $request): JsonResponse
    {
        $id = $client->getId();
        $sortRaw = $request->query->get('sort', 'bookedAt:ASC');
        $status = BookingStatusEnum::tryFrom($request->query->get('status'));
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $dto = new GetClientBookings($id, $sortRaw, $status, $page, $limit);

        $bookings = $handler->handle($dto);

        return $this->json($bookings, 200);
    }

    #[Route('api/me/bookings/{id}', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    public function get(#[CurrentUser] ?Client $client, int $id, BookingRepository $bookingRepo): JsonResponse
    {
        $booking = $bookingRepo->findOneBy([
            "client" => $client,
            "id" => $id
        ]) ?? throw new NotFoundHttpException('Booking not found');

        return $this->json($booking, 200);
    }

    #[Route('api/me/bookings', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: BookingRequest::class))]
    #[IsGranted('ROLE_CLIENT')]
    public function create(
        #[CurrentUser] ?Client $client,
        #[MapRequestPayload] BookingRequest $dto,
        BookingManager $manager
    ): Booking
    {
        return $manager->create($client, $dto);
    }

    #[Route('api/me/bookings/{id}', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Booking::class, groups: ['create-update-booking']))]
    #[IsGranted('ROLE_CLIENT')]
    public function update(
        #[CurrentUser] ?Client $client,
        int $id,
        Request $request,
        ValidatorInterface $validator,
        BookingRepository $bookingRepo,
        TrainingRepository $trainingRepo,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $booking = $bookingRepo->findOneBy([
            "client" => $client,
            "id" => $id
        ]);
        if(is_null($booking)) {
            return $this->json(['error' => "Booking not found"], 404);
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
                return $this->json(['error' => 'Training not found'], 404);
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

    #[Route('api/me/bookings/{id}', methods: ['DELETE'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    public function remove(
        #[CurrentUser] ?Client $client,
        int $id,
        BookingRepository $bookingRepo
    ): JsonResponse
    {
        $booking = $bookingRepo->findOneBy([
            "client" => $client,
            "id" => $id
        ]);
        if(is_null($booking)) {
            return $this->json(['error' => "Booking not found"], 404);
        }
        try {
            $bookingRepo->remove($booking);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
