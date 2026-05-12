<?php

namespace App\Controller\Admin;

use App\Client\Entity\Client;
use App\Payment\DTO\PaymentResponse;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Factory\GetPaymentsFactory;
use App\Payment\Mapper\PaymentMapperInterface;
use App\Payment\Query\PaymentsQuery;
use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PaymentController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
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
                description: 'List of all payments',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: PaymentResponse::class))),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Admin access required')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        Request $request,
        PaymentMapperInterface $mapper,
        PaymentsQuery $handler,
        GetPaymentsFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request);
        $payments = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn($payment) => $mapper->map($payment), $payments),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/clients/{id}/payments/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetClientPayments',
        summary: 'Get payments for a specific client (Admin).',
        tags: ['Admin: Payments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'trainerId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'minAmount', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'maxAmount', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'category', in: 'query', schema: new OA\Schema(type: 'string', enum: PaymentCategoryEnum::class)),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client payment history',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: PaymentResponse::class)))
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Client not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllByClient(
        Client $client,
        Request $request,
        PaymentMapperInterface $mapper,
        PaymentsQuery $handler,
        GetPaymentsFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest(request: $request, client: $client);
        $payments = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn($payment) => $mapper->map($payment), $payments),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/payments/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetPaymentById',
        summary: 'Get payment details (Admin).',
        tags: ['Admin: Payments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment details',
                content: new OA\JsonContent(ref: new Model(type: PaymentResponse::class))
            ),
            new OA\Response(response: 404, description: 'Payment not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function get(Payment $payment, PaymentMapperInterface $mapper): ItemResponse
    {
        return new ItemResponse(data: $mapper->map($payment), status: Response::HTTP_OK);
    }
}
