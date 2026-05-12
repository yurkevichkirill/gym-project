<?php

namespace App\Controller\Client;

use App\Client\Entity\Client;
use App\Payment\DTO\PaymentResponse;
use App\Payment\Entity\Payment;
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
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PaymentController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
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
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: PaymentStatusEnum::class), example: "succeeded"),
            new OA\Parameter(name: 'minCreateAt', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: "2026-01-01"),
            new OA\Parameter(name: 'maxCreateAt', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: "2026-01-01"),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'paidAt:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: PaymentResponse::class))),
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
        #[CurrentUser] Client $client,
        Request $request,
        PaymentMapperInterface $mapper,
        GetPaymentsFactory $factory,
        PaymentsQuery $handler,
    ): CollectionResponse
    {
        $queryDto = $factory->fromRequest(
            request: $request,
            client: $client,
        );

        $payments = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($payment) => $mapper->map($payment), $payments),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/me/payments/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getPaymentById',
        summary: 'Get details of a specific payment.',
        tags: ['Client: Payments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment details',
                content: new OA\JsonContent(ref: new Model(type: PaymentResponse::class))
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Access Denied'),
            new OA\Response(response: 404, description: 'Payment not found')
        ]
    )]
    public function get(
        Payment $payment,
        PaymentMapperInterface $mapper,
    ): ItemResponse
    {
        $this->denyAccessUnlessGranted("PAYMENT_VIEW", $payment);

        return new ItemResponse(
            data: $mapper->map($payment),
            status: Response::HTTP_OK,
        );
    }
}
