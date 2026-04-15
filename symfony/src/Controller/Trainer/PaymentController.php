<?php

declare(strict_types=1);

namespace App\Controller\Trainer;

use App\Payment\Entity\Payment;
use App\Payment\Factory\GetPaymentsFactory;
use App\Payment\Mapper\PaymentMapperInterface;
use App\Payment\Query\PaymentsQuery;
use App\Response\OkResponse;
use App\Trainer\Entity\Trainer;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

class PaymentController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainer/payments/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'clientId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'minAmount', in: 'query', example: 2000)]
    #[OA\Parameter(name: 'maxAmount', in: 'query', example: 10000)]
    #[OA\Parameter(name: 'isRefund', in: 'query', example: false)]
    #[OA\Parameter(name: 'status', in: 'query', example: "succeeded")]
    #[OA\Parameter(name: 'minCreateAt', in: 'query', example: "2026-01-01")]
    #[OA\Parameter(name: 'maxCreateAt', in: 'query', example: "2026-01-01")]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'paidAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Trainer: Payments")]
    #[IsGranted('ROLE_TRAINER')]
    public function getAll(
        #[CurrentUser] Trainer $trainer,
        Request $request,
        PaymentMapperInterface $mapper,
        GetPaymentsFactory $factory,
        PaymentsQuery $handler,
    ): OkResponse
    {
        $queryDto = $factory->fromRequest(
            request: $request,
            trainer: $trainer
        );

        $payments = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($payment) => $mapper->map($payment), $payments),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/trainer/payments/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Trainer: Payments")]
    public function get(
        Payment $payment,
        PaymentMapperInterface $mapper,
    ): OkResponse
    {
        $this->denyAccessUnlessGranted("PAYMENT_VIEW", $payment);

        return new OkResponse(
            data: $mapper->map($payment),
            status: Response::HTTP_OK,
        );
    }
}
