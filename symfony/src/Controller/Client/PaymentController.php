<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Payment\DTO\PaymentResponseDTO;
use App\Payment\DTO\ResolvedPaymentsRequestDTO;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Mapper\PaymentMapperInterface;
use App\Payment\Query\PaymentsQuery;
use App\Response\ResponseTypeDTO\CollectionResponse;
use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\SwaggerDocDTO\AbstractCollectionResponseDTO;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use App\User\Enum\UserRolesEnum;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PaymentController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws BadRequestHttpException
     */
    #[Route('/api/me/payments/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getClientPayments',
        summary: 'Get a list of client payments with filters.',
        tags: ['Client: Payments'],
        parameters: [
            new OA\Parameter(name: 'trainerId', in: 'query', schema: new OA\Schema(type: 'integer'), example: 6),
            new OA\Parameter(name: 'minAmount', in: 'query', schema: new OA\Schema(type: 'integer'), example: 2000),
            new OA\Parameter(name: 'maxAmount', in: 'query', schema: new OA\Schema(type: 'integer'), example: 10000),
            new OA\Parameter(name: 'isRefund', in: 'query', schema: new OA\Schema(type: 'boolean'), example: false),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: PaymentStatusEnum::class), example: 'succeeded'),
            new OA\Parameter(name: 'minCreatedAt', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-01-01'),
            new OA\Parameter(name: 'maxCreatedAt', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-05-12'),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'paidAt:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Collection of client payments',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractCollectionResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: new Model(type: PaymentResponseDTO::class))
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
                description: 'Trainer not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_CLIENT->value)]
    public function getAll(
        ResolvedPaymentsRequestDTO $resolvedDto,
        PaymentsQuery $handler,
    ): CollectionResponse {
        $parsedSort = $handler->getParsedSort($resolvedDto);

        $cachedData = $handler->getCachedData($resolvedDto, $parsedSort);

        return new CollectionResponse(
            $cachedData['items'],
            $resolvedDto->page,
            $resolvedDto->limit,
            $cachedData['total'],
            $parsedSort,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws AccessDeniedException
     * @throws AccessDeniedException
     */
    #[Route('/api/me/payments/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getClientPaymentById',
        summary: 'Get details of a specific payment.',
        tags: ['Client: Payments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment details',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: PaymentResponseDTO::class)
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
                description: 'Payment not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_CLIENT->value)]
    public function get(
        Payment $payment,
        PaymentMapperInterface $mapper,
    ): ItemResponse {
        $this->denyAccessUnlessGranted('PAYMENT_VIEW', $payment);

        return new ItemResponse(
            data: $mapper->map($payment),
            status: Response::HTTP_OK,
        );
    }
}
