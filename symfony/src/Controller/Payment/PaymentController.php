<?php

declare(strict_types=1);

namespace App\Controller\Payment;

use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Service\StripeService;
use App\Response\ItemResponse;
use App\Response\OkResponse;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
use Throwable;

class PaymentController extends AbstractController
{
    /**
     * @throws ApiErrorException|Throwable
     */
    #[Route('/api/payments/{id}/intent/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Client: Payments")]
    #[IsGranted('ROLE_CLIENT')]
    public function createIntent(
        Payment $payment,
        StripeService $stripeService
    ): ItemResponse {
        $this->denyAccessUnlessGranted('PAYMENT_VIEW', $payment);

        if ($payment->getStatus() !== PaymentStatusEnum::PENDING) {
            throw new BadRequestHttpException('Payment already processed');
        }

        $clientSecret = $stripeService->createPaymentIntent($payment);

        return new ItemResponse([
            'clientSecret' => $clientSecret
        ]);
    }
}
