<?php

namespace App\Controller\Admin;

use App\Booking\DTO\BookingRequest;
use App\Booking\DTO\GetBookings;
use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Factory\GetBookingsFactory;
use App\Booking\Mapper\BookingAdminMapperInterface;
use App\Booking\Mapper\BookingMapperInterface;
use App\Booking\Query\BookingsQuery;
use App\Booking\Service\BookingManager;
use App\Client\Entity\Client;
use App\Response\OkResponse;
use App\Trainer\Entity\Trainer;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class BookingController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/bookings/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'trainerId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'clientId', in: 'query', example: 6)]
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
        GetBookingsFactory     $factory,
    ): OkResponse
    {
        $dto = $factory->fromRequest(
            request: $request,
        );

        $bookings = $handler->handle($dto);

        return new OkResponse(
            array_map(fn($booking) => $mapper->map($booking), $bookings),
            $dto->page,
            $dto->limit,
            $handler->getTotal($dto->filter),
            $dto->sort,
            Response::HTTP_OK
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
        GetBookingsFactory     $factory,
    ): OkResponse
    {
        $dto = $factory->fromRequest(
            request: $request,
            client: $client,
        );

        $bookings = $handler->handle($dto);

        return new OkResponse(
            array_map(fn($booking) => $mapper->map($booking), $bookings),
            $dto->page,
            $dto->limit,
            $handler->getTotal($dto->filter),
            $dto->sort,
            Response::HTTP_OK
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainers/{id}/bookings/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'clientId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'scheduled')]
    #[OA\Parameter(name: 'date', in: 'query', example: '2026-03-10')]
    #[OA\Parameter(name: 'startTime', in: 'query', example: '15:00:00')]
    #[OA\Parameter(name: 'durationMinutes', in: 'query', example: 90)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'bookedAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Bookings")]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllByTrainer(
        BookingMapperInterface $mapper,
        Trainer $trainer,
        BookingsQuery $handler,
        Request $request,
        GetBookingsFactory $factory,
    ): OkResponse
    {
        $queryDto = $factory->fromRequest(
            request: $request,
            trainer: $trainer,
        );

        $trainings = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($training) => $mapper->map($training), $trainings),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('api/bookings/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Admin: Bookings")]
    #[IsGranted('ROLE_ADMIN')]
    public function get(BookingAdminMapperInterface $mapper, Booking $booking): OkResponse
    {
        return new OkResponse(
            data: $mapper->map($booking),
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    #[Route('api/clients/{id}/bookings/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: BookingRequest::class))]
    #[OA\Tag(name: "Admin: Bookings")]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        BookingAdminMapperInterface         $mapper,
        Client                              $client,
        #[MapRequestPayload] BookingRequest $requestDto,
        BookingManager                      $manager
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->book($client, $requestDto));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_CREATED,
        );
    }

    #[Route('api/bookings/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Admin: Bookings")]
    public function remove(
        Booking $booking,
        BookingManager $manager,
    ): Response
    {
        $this->denyAccessUnlessGranted("BOOKING_REMOVE", $booking);

        $manager->cancelBooking($booking, BookingStatusEnum::CANCELED_BY_SYSTEM);

        return new Response(
            status: Response::HTTP_NO_CONTENT
        );
    }
}
