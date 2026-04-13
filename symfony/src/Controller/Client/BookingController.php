<?php

namespace App\Controller\Client;

use App\Booking\DTO\BookingRequest;
use App\Booking\DTO\GetBookings;
use App\Booking\Entity\Booking;
use App\Booking\Mapper\BookingMapperInterface;
use App\Booking\Query\BookingsQuery;
use App\Booking\Service\BookingManager;
use App\Client\Entity\Client;
use App\Response\OkResponse;
use App\Trainer\Repository\TrainerRepository;
use DateMalformedStringException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class BookingController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/me/bookings/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'trainerId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'scheduled')]
    #[OA\Parameter(name: 'date', in: 'query', example: '10-03-2026')]
    #[OA\Parameter(name: 'startTime', in: 'query', example: '15:00:00')]
    #[OA\Parameter(name: 'durationMinutes', in: 'query', example: 90)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'bookedAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Client: Bookings")]
    #[IsGranted('ROLE_CLIENT')]
    public function getAll(
        BookingMapperInterface $mapper,
        #[CurrentUser] Client  $client,
        BookingsQuery          $handler,
        Request                $request,
        TrainerRepository      $trainerRepo,
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'bookedAt:ASC');
        if ($request->query->get('trainerId')) {
            $trainer = $trainerRepo->find((int) $request->query->get('trainerId'));

            if (is_null($trainer)) {
                throw new NotFoundHttpException("Trainer not found");
            }
        } else {
            $trainer = null;
        }
        $status = $request->query->get('status');
        $date = $request->query->get('date');
        $durationMinutes = $request->query->get('durationMinutes') !== null ? (int) $request->query->get('durationMinutes') : null;
        $startTime = $request->query->get('startTime');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetBookings(
            $sortRaw,
            $trainer,
            $date,
            $durationMinutes,
            $startTime,
            $status,
            $page,
            $limit,
            $client,
        );

        $bookings = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn($booking) => $mapper->map($booking), $bookings),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            200
        );
    }

    #[Route('/api/me/bookings/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Client: Bookings")]
    public function get(BookingMapperInterface $mapper, Booking $booking): OkResponse
    {
        $this->denyAccessUnlessGranted('BOOKING_VIEW', $booking);

        return new OkResponse(
            data: $mapper->map($booking),
            status: 200,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    #[Route('/api/me/bookings/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: BookingRequest::class))]
    #[OA\Tag(name: "Client: Bookings")]
    #[IsGranted('ROLE_CLIENT')]
    public function create(
        BookingMapperInterface              $mapper,
        #[CurrentUser] Client               $client,
        #[MapRequestPayload] BookingRequest $requestDto,
        BookingManager                      $manager
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->book($client, $requestDto));

        return new OkResponse(
            data: $responseDto,
            status: 201,
        );
    }

    #[Route('/api/me/bookings/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Client: Bookings")]
    public function remove(
        Booking $booking,
        BookingManager $manager,
    ): Response
    {
        $this->denyAccessUnlessGranted("BOOKING_REMOVE", $booking);

        $manager->cancelBooking($booking);

        return new Response(
            status: 204
        );
    }
}
