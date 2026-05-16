<?php

declare(strict_types=1);

namespace App\Controller\Trainer;

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
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PaymentController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws BadRequestHttpException
     */
    #[Route('/api/trainer/payments/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getTrainerPayments',
        summary: 'Get a list of trainer payments with filters.',
        tags: ['Trainer: Payments'],
        parameters: [
            new OA\Parameter(name: 'clientId', in: 'query', schema: new OA\Schema(type: 'integer'), example: 6),
            new OA\Parameter(name: 'minAmount', in: 'query', schema: new OA\Schema(type: 'integer'), example: 2000),
            new OA\Parameter(name: 'maxAmount', in: 'query', schema: new OA\Schema(type: 'integer'), example: 10000),
            new OA\Parameter(name: 'isRefund', in: 'query', schema: new OA\Schema(type: 'boolean'), example: false),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: PaymentStatusEnum::class), example: "succeeded"),
            new OA\Parameter(name: 'minCreatedAt', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: "2026-01-01"),
            new OA\Parameter(name: 'maxCreatedAt', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: "2026-05-12"),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'paidAt:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Collection of trainer payments',
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
                description: 'Client not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
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
     */
    #[Route('/api/trainer/payments/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getTrainerPaymentById',
        summary: 'Get details of a specific payment.',
        tags: ['Trainer: Payments'],
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
    #[IsGranted('ROLE_TRAINER')]
    public function get(
        Payment $payment,
        PaymentMapperInterface $mapper,
    ): ItemResponse {
        $this->denyAccessUnlessGranted("PAYMENT_VIEW", $payment);

        return new ItemResponse(
            data: $mapper->map($payment),
            status: Response::HTTP_OK,
        );
    }
}
