<?php

declare(strict_types=1);

namespace App\Controller\Payment;

use App\Payment\Service\StripeWebhookService;
use App\Response\NoContentResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

final class WebhookController extends AbstractController
{
    #[Route('/api/webhooks/stripe/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'stripeWebhook',
        description: 'Endpoint for Stripe to send asynchronous event notifications (payment success, failure, etc.). Requires a valid stripe-signature header.',
        summary: 'Handle Stripe webhooks.',
        tags: ['Payment: Webhook'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Webhook handled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Invalid payload or signature verification failed.'
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
