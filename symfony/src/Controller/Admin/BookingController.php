<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Booking\DTO\BookingAdminResponseDTO;
use App\Booking\DTO\BookingRequestDTO;
use App\Booking\DTO\BookingResponseDTO;
use App\Booking\DTO\ResolvedBookingsRequestDTO;
use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Exception\InvalidBookingStatusException;
use App\Booking\Mapper\BookingAdminMapperInterface;
use App\Booking\Query\AdminBookingsQuery;
use App\Booking\Security\BookingVoter;
use App\Booking\Service\BookingCancellationService;
use App\Booking\Service\BookingManager;
use App\Client\Entity\Client;
use App\Response\ResponseTypeDTO\CollectionResponse;
use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\SwaggerDocDTO\AbstractCollectionResponseDTO;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use App\User\Entity\User;
use App\User\Enum\UserRolesEnum;
use DateMalformedStringException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class BookingController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws BadRequestHttpException
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
            new OA\Parameter(name: 'startTime', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'durationMinutes', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 30)),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Collection of bookings',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractCollectionResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: new Model(type: BookingAdminResponseDTO::class))
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid query parameters',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Trainer or Client not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function getAll(
        ResolvedBookingsRequestDTO $resolvedDto,
        AdminBookingsQuery              $handler,
    ): CollectionResponse {
        $parsedSort = $handler->getParsedSort($resolvedDto);

        $cachedData = $handler->getCachedData($resolvedDto, $parsedSort);

        return new CollectionResponse(
            $cachedData['items'],
            $resolvedDto->page,
            $resolvedDto->limit,
            $cachedData['total'],
            $parsedSort,
            Response::HTTP_OK
        );
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route('/api/bookings/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetBookingById',
        summary: 'Get booking details (Admin).',
        tags: ['Admin: Bookings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Booking details',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: BookingAdminResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Booking not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function get(BookingAdminMapperInterface $mapper, Booking $booking): ItemResponse
    {
        $this->denyAccessUnlessGranted(BookingVoter::VIEW_ADMIN, $booking);

        return new ItemResponse(data: $mapper->map($booking), status: Response::HTTP_OK);
    }

    #[Route('/api/coffee/', methods: ['GET', 'POST'])]
    public function brewCoffee(): JsonResponse
    {
        return new JsonResponse([
            'error' => "I'm a teapot",
            'message' => 'This server is a teapot, not a coffee machine. Go to the gym instead!'
        ], 418);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     */
    #[Route('/api/clients/{id}/bookings/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCreateBookingForClient',
        summary: 'Create a booking for a specific client (Admin).',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: BookingRequestDTO::class))),
        tags: ['Admin: Bookings'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Client ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Booking successfully created',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: BookingAdminResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request (e.g. Booking in the past, No active membership, Invalid payment status)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Insufficient admin rights or Client is blocked/inactive)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found (Client, Trainer, or Worktime not found)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (Client already has training, or Time is already taken)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error (Payload format or constraint constraints failed)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function create(
        BookingAdminMapperInterface            $mapper,
        Client                                 $client,
        #[MapRequestPayload] BookingRequestDTO $requestDto,
        BookingManager                         $manager
    ): ItemResponse {
        $responseDto = $mapper->map($manager->book($client, $requestDto));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_CREATED);
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidBookingStatusException
     * @throws Throwable
     */
    #[Route('/api/bookings/{id}/cancel/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCancelBooking',
        summary: 'Cancel booking (Admin).',
        tags: ['Admin: Bookings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Booking cancelled successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: new Model(type: BookingAdminResponseDTO::class))
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request / Business Logic Error (e.g. Cannot transition payment status to REFUNDED, or Booking already canceled)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Insufficient admin rights to cancel this specific booking)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Booking not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function cancel(
        Booking                    $booking,
        #[CurrentUser] User        $actor,
        BookingCancellationService $bookingCancellationService,
        BookingAdminMapperInterface            $mapper,
    ): ItemResponse {
        $this->denyAccessUnlessGranted(BookingVoter::CANCEL_ADMIN, $booking);

        $bookingCancellationService->cancel($booking, $actor);
        $responseDto = $mapper->map($booking);

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }
}
