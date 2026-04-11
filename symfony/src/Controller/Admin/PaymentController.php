<?php

namespace App\Controller\Admin;

use App\Client\Entity\Client;
use App\Payment\DTO\GetPayments;
use App\Payment\Entity\Payment;
use App\Payment\Mapper\PaymentMapperInterface;
use App\Payment\Query\PaymentsQuery;
use App\Response\OkResponse;
use App\Trainer\Repository\TrainerRepository;
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
    #[OA\Parameter(name: 'trainerId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'minAmount', in: 'query', example: 20)]
    #[OA\Parameter(name: 'maxAmount', in: 'query', example: 100)]
    #[OA\Parameter(name: 'category', in: 'query', example: 'membership')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'paidAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Payments")]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        Request $request,
        PaymentMapperInterface $mapper,
        PaymentsQuery $handler,
        TrainerRepository $trainerRepo,
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'paidAt:ASC');
        $trainer = $trainerRepo->find((int) $request->query->get('trainerId'));
        $category = $request->query->get('category');
        $minAmount = $request->query->get('minAmount');
        $maxAmount = $request->query->get('maxAmount');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetPayments(
            $sortRaw,
            $trainer,
            $minAmount,
            $maxAmount,
            $category,
            $page,
            $limit,
        );

        $payments = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($payment) => $mapper->map($payment), $payments),
            $page,
            $limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/clients/{id}/payments/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'trainerId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'minAmount', in: 'query', example: 20)]
    #[OA\Parameter(name: 'maxAmount', in: 'query', example: 100)]
    #[OA\Parameter(name: 'category', in: 'query', example: 'membership')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'paidAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Payments")]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllByClient(
        Client $client,
        Request $request,
        PaymentMapperInterface $mapper,
        PaymentsQuery $handler,
        TrainerRepository $trainerRepo,
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'paidAt:ASC');
        $trainer = $trainerRepo->find((int) $request->query->get('trainerId'));
        $category = $request->query->get('category');
        $minAmount = $request->query->get('minAmount');
        $maxAmount = $request->query->get('maxAmount');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetPayments(
            $sortRaw,
            $trainer,
            $minAmount,
            $maxAmount,
            $category,
            $page,
            $limit,
            $client,
        );

        $payments = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($payment) => $mapper->map($payment), $payments),
            $page,
            $limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/payments/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Admin: Payments")]
    #[IsGranted('ROLE_ADMIN')]
    public function get(
        Payment $payment,
        PaymentMapperInterface $mapper,
    ): OkResponse
    {
        return new OkResponse(
            data: $mapper->map($payment),
            status: Response::HTTP_OK,
        );
    }
}
