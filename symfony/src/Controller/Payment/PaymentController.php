<?php

declare(strict_types=1);

namespace App\Controller\Payment;

use App\Payment\DTO\StripeIntentResponseDTO;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Service\PaymentSettlementService;
use App\Response\ItemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
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
            new OA\Parameter(
                name: 'id',
                description: 'Payment ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Stripe Intent successfully created.',
                content: new OA\JsonContent(ref: new Model(type: StripeIntentResponseDTO::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Payment already processed or invalid state.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Payment already processed')
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Access denied to this payment'),
            new OA\Response(response: 404, description: 'Payment not found')
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
