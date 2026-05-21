<?php

declare(strict_types=1);

namespace App\Controller\Payment;

use App\Payment\Service\StripeWebhookService;
use App\Response\ResponseTypeDTO\NoContentResponse;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class WebhookController extends AbstractController
{
    /**
     * @throws BadRequestHttpException
     * @throws Throwable
     */
    #[Route('/api/webhooks/stripe/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'stripeWebhook',
        description: 'Endpoint for Stripe to send asynchronous event notifications (payment success, failure, etc.). Requires a valid stripe-signature header.',
        summary: 'Handle Stripe webhooks.',
        tags: ['Payment: Webhook'],
        parameters: [
            new OA\Parameter(
                name: 'stripe-signature',
                description: 'Stripe webhook signature for payload verification',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Webhook handled successfully'
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request (Invalid payload or signature verification failed)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    public function handleWebhook(
        Request $request,
        StripeWebhookService $webhookService
    ): NoContentResponse {
        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');

        $webhookService->handle($payload, $signature);

        return new NoContentResponse();
    }
}
