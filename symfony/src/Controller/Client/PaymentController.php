<?php

namespace App\Controller\Client;

use App\Client\Entity\Client;
use App\Payment\Entity\Payment;
use App\Payment\Factory\GetPaymentsFactory;
use App\Payment\Mapper\PaymentMapperInterface;
use App\Payment\Query\PaymentsQuery;
use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Response\OkResponse;
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
    #[OA\Parameter(name: 'trainerId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'minAmount', in: 'query', example: 2000)]
    #[OA\Parameter(name: 'maxAmount', in: 'query', example: 10000)]
    #[OA\Parameter(name: 'isRefund', in: 'query', example: false)]
    #[OA\Parameter(name: 'status', in: 'query', example: "succeeded")]
    #[OA\Parameter(name: 'minCreateAt', in: 'query', example: "2026-01-01")]
    #[OA\Parameter(name: 'maxCreateAt', in: 'query', example: "2026-01-01")]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'paidAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Client: Payments")]
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
    #[OA\Tag(name: "Client: Payments")]
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
