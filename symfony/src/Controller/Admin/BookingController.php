<?php

namespace App\Controller\Admin;

use App\Booking\DTO\BookingRequest;
use App\Booking\DTO\GetClientBookings;
use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Mapper\BookingMapperInterface;
use App\Booking\Query\ClientBookingsQuery;
use App\Booking\Repository\BookingRepository;
use App\Booking\Service\BookingManager;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Response\OkResponse;
use App\Training\Repository\TrainingRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
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
    #[Route('/api/clients/{id}/bookings', methods: ['GET'], format: 'json')]
    #[OA\Parameter(
        name: 'status',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        int $id,
        Request $request,
        BookingMapperInterface $mapper,
        ClientBookingsQuery $handler,
        BookingRepository $repo,
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'bookedAt:ASC');
        $status = BookingStatusEnum::tryFrom($request->query->get('status'));
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetClientBookings($id, $sortRaw, $status, $page, $limit);

        $bookings =  $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn($booking) => $mapper->map($booking), $bookings),
            $queryDto->page,
            $queryDto->limit,
            $repo->count(['client' => $bookings[0]->getClient()]),
            $queryDto->sort,
            200
        );
    }

    #[Route('api/bookings/{bookingId}', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_ADMIN')]
    public function get(int $bookingId, BookingRepository $bookingRepo, BookingMapperInterface $mapper): OkResponse
    {
        $booking = $bookingRepo->findOneBy(['id' => $bookingId]);

        return new OkResponse(
            data: $mapper->map($booking),
            status: 200,
        );
    }

    #[Route('api/clients/{id}/bookings', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: BookingRequest::class))]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        int $id,
        BookingMapperInterface $mapper,
        ClientRepository $clientRepo,
        #[MapRequestPayload] BookingRequest $dto,
        BookingManager $manager
    ): OkResponse
    {
        $client = $clientRepo->find($id);

        $dto = $mapper->map($manager->create($client, $dto));

        return new OkResponse(
            data: $dto,
            status: 201,
        );

    }

    #[Route('api/bookings/{id}', methods: ['DELETE'], format: 'json')]
    #[IsGranted('ROLE_ADMIN')]
    public function remove(
        Booking $booking,
        BookingRepository $bookingRepo
    ): Response
    {
        $bookingRepo->remove($booking);

        return new Response(status: 204);
    }
}
