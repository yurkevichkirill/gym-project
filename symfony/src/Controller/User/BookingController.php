<?php

namespace App\Controller\User;

use App\Booking\DTO\BookingRequest;
use App\Booking\DTO\GetClientBookings;
use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Mapper\BookingMapperInterface;
use App\Booking\Query\ClientBookingsQuery;
use App\Booking\Repository\BookingRepository;
use App\Booking\Service\BookingManager;
use App\Client\Entity\Client;
use App\Response\OkResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    #[OA\Tag(name: "Bookings")]
    #[IsGranted('ROLE_CLIENT')]
    public function getAll(
        BookingMapperInterface $mapper,
        #[CurrentUser] Client $client,
        ClientBookingsQuery $handler,
        Request $request,
        BookingRepository $repo,
    ): OkResponse
    {
        $id = $client->getId();
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

    #[Route('api/me/bookings/{id}', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Bookings")]
    public function get(BookingMapperInterface $mapper, Booking $booking): OkResponse
    {
        $this->denyAccessUnlessGranted('BOOKING_VIEW', $booking);

        return new OkResponse(
            data: $mapper->map($booking),
            status: 200,
        );
    }

    #[Route('api/me/bookings', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: BookingRequest::class))]
    #[OA\Tag(name: "Bookings")]
    #[IsGranted('ROLE_CLIENT')]
    public function create(
        BookingMapperInterface $mapper,
        #[CurrentUser] Client $client,
        #[MapRequestPayload] BookingRequest $dto,
        BookingManager $manager
    ): OkResponse
    {
        $dto = $mapper->map($manager->create($client, $dto));

        return new OkResponse(
            data: $dto,
            status: 201,
        );
    }

    #[Route('api/me/bookings/{id}', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Bookings")]
    public function remove(
        Booking $booking,
        BookingRepository $bookingRepo
    ): Response
    {
        $this->denyAccessUnlessGranted("BOOKING_REMOVE", $booking);

        $bookingRepo->remove($booking);

        return new Response(
            status: 204
        );
    }
}
