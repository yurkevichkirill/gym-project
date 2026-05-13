<?php

namespace App\Controller\Admin;

use App\Booking\DTO\BookingRequestDTO;
use App\Booking\DTO\BookingResponseDTO;
use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Factory\GetBookingsFactory;
use App\Booking\Mapper\BookingAdminMapperInterface;
use App\Booking\Mapper\BookingMapperInterface;
use App\Booking\Query\BookingsQuery;
use App\Booking\Service\BookingCancellationService;
use App\Booking\Service\BookingManager;
use App\Client\Entity\Client;
use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use App\Trainer\Entity\Trainer;
use App\User\Entity\User;
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
    #[Route('/api/bookings/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetBookings',
        summary: 'Get all bookings (Admin).',
        tags: ['Admin: Bookings'],
        parameters: [
            new OA\Parameter(name: 'trainerId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'clientId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: BookingStatusEnum::class)),
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Collection of bookings',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: BookingResponseDTO::class))),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        BookingAdminMapperInterface $mapper,
        BookingsQuery          $handler,
        Request                $request,
        GetBookingsFactory     $factory,
    ): CollectionResponse {
        $dto = $factory->fromRequest(request: $request);
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

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/clients/{id}/bookings/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetClientBookings',
        summary: 'Get bookings for a specific client.',
        tags: ['Admin: Bookings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: BookingStatusEnum::class)),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: BookingResponseDTO::class)))
            ])),
            new OA\Response(response: 404, description: 'Client not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllByClient(
        BookingAdminMapperInterface $mapper,
        Client                 $client,
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

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainers/{id}/bookings/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetTrainerBookings',
        summary: 'Get bookings for a specific trainer.',
        tags: ['Admin: Bookings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: BookingResponseDTO::class)))
            ]))
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllByTrainer(
        BookingMapperInterface $mapper,
        Trainer $trainer,
        BookingsQuery $handler,
        Request $request,
        GetBookingsFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest(request: $request, trainer: $trainer);
        $trainings = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($training) => $mapper->map($training), $trainings),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/bookings/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetBookingById',
        summary: 'Get booking details (Admin).',
        tags: ['Admin: Bookings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success', content: new OA\JsonContent(ref: new Model(type: BookingResponseDTO::class))),
            new OA\Response(response: 404, description: 'Booking not found')
        ]
    )]
    public function get(BookingAdminMapperInterface $mapper, Booking $booking): ItemResponse
    {
        $this->denyAccessUnlessGranted('BOOKING_VIEW_ADMIN', $booking);

        return new ItemResponse(data: $mapper->map($booking), status: Response::HTTP_OK);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws Throwable
     */
    #[Route('/api/clients/{id}/bookings/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCreateBookingForClient',
        summary: 'Create a booking for a specific client (Admin).',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: BookingRequestDTO::class))),
        tags: ['Admin: Bookings'],
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: new Model(type: BookingResponseDTO::class))),
            new OA\Response(response: 400, description: 'Bad Request', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string')
            ])),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        BookingAdminMapperInterface            $mapper,
        Client                                 $client,
        #[MapRequestPayload] BookingRequestDTO $requestDto,
        BookingManager                         $manager
    ): ItemResponse {
        $responseDto = $mapper->map($manager->book($client, $requestDto));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_CREATED);
    }

    #[Route('/api/bookings/{id}/cancel/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCancelBooking',
        summary: 'Cancel booking (Admin).',
        tags: ['Admin: Bookings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Cancelled successfully'),
            new OA\Response(response: 403, description: 'Access Denied'),
            new OA\Response(response: 404, description: 'Booking not found')
        ]
    )]
    public function cancel(
        Booking $booking,
        #[CurrentUser] User $actor,
        BookingCancellationService $bookingCancellationService,
    ): NoContentResponse {
        $this->denyAccessUnlessGranted('BOOKING_CANCEL_ADMIN', $booking);
        $bookingCancellationService->cancel($booking, $actor);

        return new NoContentResponse();
    }
}
