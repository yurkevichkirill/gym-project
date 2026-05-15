<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Payment\DTO\PaymentResponse;
use App\Payment\DTO\ResolvedPaymentsRequestDTO;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Mapper\PaymentMapperInterface;
use App\Payment\Query\PaymentsQuery;
use App\Response\CollectionResponse;
use App\Response\DTO\AbstractCollectionResponseDTO;
use App\Response\DTO\AbstractItemResponseDTO;
use App\Response\DTO\ErrorResponseDTO;
use App\Response\ItemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PaymentController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws BadRequestHttpException
     */
    #[Route('/api/payments/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetPayments',
        summary: 'Get all payments with advanced filters (Admin).',
        tags: ['Admin: Payments'],
        parameters: [
            new OA\Parameter(name: 'trainerId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'clientId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'minAmount', in: 'query', schema: new OA\Schema(type: 'integer'), example: 2000),
            new OA\Parameter(name: 'maxAmount', in: 'query', schema: new OA\Schema(type: 'integer'), example: 10000),
            new OA\Parameter(name: 'isRefund', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: PaymentStatusEnum::class)),
            new OA\Parameter(name: 'minCreateAt', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-01-01'),
            new OA\Parameter(name: 'maxCreateAt', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-05-12'),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'paidAt:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Collection of payments',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractCollectionResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: new Model(type: PaymentResponse::class))
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
    #[IsGranted('ROLE_ADMIN')]
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

    #[Route('/api/payments/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetPaymentById',
        summary: 'Get payment details (Admin).',
        tags: ['Admin: Payments'],
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
                                    ref: new Model(type: PaymentResponse::class)
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
    #[IsGranted('ROLE_ADMIN')]
    public function get(Payment $payment, PaymentMapperInterface $mapper): ItemResponse
    {
        return new ItemResponse(data: $mapper->map($payment), status: Response::HTTP_OK);
    }
}
