<?php

namespace App\Controller\Admin;

use App\Booking\DTO\BookingRequest;
use App\Booking\DTO\GetBookings;
use App\Booking\Entity\Booking;
use App\Booking\Mapper\BookingAdminMapperInterface;
use App\Booking\Query\BookingsQuery;
use App\Booking\Service\BookingManager;
use App\Client\Entity\Client;
use App\Response\OkResponse;
use App\Trainer\Repository\TrainerRepository;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class BookingController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/bookings/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'trainerId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'scheduled')]
    #[OA\Parameter(name: 'date', in: 'query', example: '10-03-2026')]
    #[OA\Parameter(name: 'startTime', in: 'query', example: '15:00:00')]
    #[OA\Parameter(name: 'durationMinutes', in: 'query', example: 90)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'bookedAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Bookings")]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        BookingAdminMapperInterface $mapper,
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
        $durationMinutes = $request->query->get('durationMinutes') ? (int) $request->query->get('durationMinutes') : null;
        $startTime = $request->query->get('startTime');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetBookings(
            $sortRaw,
            null,
            $trainer,
            $date,
            $durationMinutes,
            $startTime,
            $status,
            $page,
            $limit
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

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/clients/{id}/bookings/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'trainerId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'scheduled')]
    #[OA\Parameter(name: 'date', in: 'query', example: '10-03-2026')]
    #[OA\Parameter(name: 'startTime', in: 'query', example: '15:00:00')]
    #[OA\Parameter(name: 'durationMinutes', in: 'query', example: 90)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'bookedAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Bookings")]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllByClient(
        BookingAdminMapperInterface $mapper,
        Client                 $client,
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
        $durationMinutes = $request->query->get('durationMinutes') ? (int) $request->query->get('durationMinutes') : null;
        $startTime = $request->query->get('startTime');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetBookings(
            $sortRaw,
            $client,
            $trainer,
            $date,
            $durationMinutes,
            $startTime,
            $status,
            $page,
            $limit
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

    #[Route('api/bookings/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Admin: Bookings")]
    public function get(BookingAdminMapperInterface $mapper, Booking $booking): OkResponse
    {
        $this->denyAccessUnlessGranted('BOOKING_VIEW', $booking);

        return new OkResponse(
            data: $mapper->map($booking),
            status: 200,
        );
    }

    /**
     * @throws \DateMalformedStringException
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \DateMalformedIntervalStringException
     */
    #[Route('api/clients/{id}/bookings/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: BookingRequest::class))]
    #[OA\Tag(name: "Admin: Bookings")]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        BookingAdminMapperInterface              $mapper,
        Client                              $client,
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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('api/bookings/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Admin: Bookings")]
    public function remove(
        Booking $booking,
        BookingManager $manager,
    ): Response
    {
        $this->denyAccessUnlessGranted("BOOKING_REMOVE", $booking);

        $client = $booking->getClient();
        $manager->cancelBooking($client, $booking);

        return new Response(
            status: 204
        );
    }
}
