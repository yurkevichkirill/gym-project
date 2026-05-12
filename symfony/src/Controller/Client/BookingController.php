<?php

namespace App\Controller\Client;

use App\Booking\DTO\BookingRequest;
use App\Booking\DTO\BookingResponse;
use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Factory\GetBookingsFactory;
use App\Booking\Mapper\BookingMapperInterface;
use App\Booking\Query\BookingsQuery;
use App\Booking\Service\BookingCancellationService;
use App\Booking\Service\BookingManager;
use App\Client\Entity\Client;
use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use App\User\Entity\User;
use DateMalformedStringException;
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
use Throwable;

final class BookingController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/me/bookings/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getClientBookings',
        summary: 'Get a list of current client bookings.',
        tags: ['Client: Bookings'],
        parameters: [
            new OA\Parameter(name: 'trainerId', in: 'query', schema: new OA\Schema(type: 'integer'), example: 6),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: BookingStatusEnum::class), example: 'scheduled'),
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: '10-03-2026'),
            new OA\Parameter(name: 'startTime', in: 'query', schema: new OA\Schema(type: 'string', format: 'time'), example: '15:00:00'),
            new OA\Parameter(name: 'durationMinutes', in: 'query', schema: new OA\Schema(type: 'integer'), example: 90),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'bookedAt:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: BookingResponse::class))),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function getAll(
        BookingMapperInterface $mapper,
        #[CurrentUser] Client  $client,
        BookingsQuery          $handler,
        Request                $request,
        GetBookingsFactory     $factory,
    ): CollectionResponse {
        $dto = $factory->fromRequest(request: $request, client: $client);
        $bookings = $handler->handle($dto);

        return new CollectionResponse(
            array_map(fn($booking) => $mapper->map($booking), $bookings),
            $dto->page,
            $dto->limit,
            $handler->getTotal($dto->filter),
            $dto->sort,
            Response::HTTP_OK
        );
    }

    #[Route('/api/me/bookings/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getBookingById',
        summary: 'Get details of a specific booking.',
        tags: ['Client: Bookings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Booking details',
                content: new OA\JsonContent(ref: new Model(type: BookingResponse::class))
            ),
            new OA\Response(response: 403, description: 'Access Denied'),
            new OA\Response(response: 404, description: 'Booking not found')
        ]
    )]
    public function get(BookingMapperInterface $mapper, Booking $booking): ItemResponse
    {
        $this->denyAccessUnlessGranted('BOOKING_VIEW_OWN', $booking);

        return new ItemResponse(
            data: $mapper->map($booking),
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     */
    #[Route('/api/me/bookings/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'createBooking',
        summary: 'Create a new booking.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: BookingRequest::class))
        ),
        tags: ['Client: Bookings'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Booking created successfully',
                content: new OA\JsonContent(ref: new Model(type: BookingResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Business logic error (past date, time already taken, no membership)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Client already have training at this time'),
                        new OA\Property(property: 'request_id', type: 'string', nullable: true)
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(property: 'request_id', type: 'string', nullable: true)
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Trainer or Worktime not found')
        ]
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function create(
        BookingMapperInterface              $mapper,
        #[CurrentUser] Client               $client,
        #[MapRequestPayload] BookingRequest $requestDto,
        BookingManager                      $manager
    ): ItemResponse {
        $responseDto = $mapper->map($manager->book($client, $requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_CREATED,
        );
    }

    #[Route('/api/me/bookings/{id}/cancel/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'cancelBooking',
        summary: 'Cancel an existing booking.',
        tags: ['Client: Bookings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Booking cancelled successfully'),
            new OA\Response(
                response: 400,
                description: 'Invalid booking status for cancellation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Only scheduled bookings can be canceled by client'),
                        new OA\Property(property: 'request_id', type: 'string', nullable: true)
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Access Denied'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 409, description: 'Conflict - Refund failed or state conflict')
        ]
    )]
    public function cancel(
        Booking $booking,
        #[CurrentUser] User $actor,
        BookingCancellationService $bookingCancellationService,
    ): NoContentResponse {
        $this->denyAccessUnlessGranted("BOOKING_CANCEL_OWN", $booking);

        $bookingCancellationService->cancel($booking, $actor);

        return new NoContentResponse();
    }
}
