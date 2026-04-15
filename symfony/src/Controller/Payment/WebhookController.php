<?php

declare(strict_types=1);

namespace App\Controller\Payment;

use App\Payment\Service\StripeWebhookService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

final readonly class WebhookController
{
    #[Route('/api/webhooks/stripe/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Payment: Webhook")]
    public function handleWebhook(
        Request $request,
        StripeWebhookService $webhookService
    ): JsonResponse {
        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');

        $webhookService->handle($payload, $signature);

        return new JsonResponse(['status' => 'ok']);
    }
}
