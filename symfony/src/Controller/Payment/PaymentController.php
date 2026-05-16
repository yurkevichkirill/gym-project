<?php

declare(strict_types=1);

namespace App\Controller\Payment;

use App\Payment\DTO\StripeIntentResponseDTO;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Service\PaymentSettlementService;
use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class PaymentController extends AbstractController
{
    /**
     * @throws ApiErrorException|Throwable
     */
    #[Route('/api/payments/{id}/intent/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'createStripeIntent',
        summary: 'Create a Stripe Payment Intent for a specific payment.',
        tags: ['Client: Payments'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Payment ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Stripe Intent successfully created',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: StripeIntentResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request (e.g., Payment already processed)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Access denied to this payment)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Payment not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function createIntent(
        Payment $payment,
        PaymentSettlementService $paymentSettlementService,
    ): ItemResponse {
        $this->denyAccessUnlessGranted('PAYMENT_VIEW', $payment);

        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            throw new BadRequestHttpException('Payment already processed');
        }

        $clientSecret = $paymentSettlementService->createStripeIntent($payment);

        return new ItemResponse(
            data: new StripeIntentResponseDTO($clientSecret),
            status: Response::HTTP_CREATED,
        );
    }
}
