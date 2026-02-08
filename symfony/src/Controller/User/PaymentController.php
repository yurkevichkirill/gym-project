<?php

namespace App\Controller\User;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Repository\PaymentRepository;
use App\Payment\Service\PaymentServiceInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class PaymentController extends AbstractController
{
    #[Route('/api/me/payments', methods: ['GET'], format: 'json')]
    #[OA\Parameter(
        name: 'status',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'category',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function getAll(Request $request, PaymentServiceInterface $paymentService, #[CurrentUser] ?Client $client): JsonResponse
    {
        try {
            $sortRaw = $request->query->get('sort', 'paidAt:ASC');
            $sort = [];
            foreach (explode(',', $sortRaw) as $item) {
                [$field, $order] = explode(':',  $item);
                $sort[$field] = strtoupper($order);
            }
            $status = PaymentStatusEnum::tryFrom($request->query->get('status'));
            $category = PaymentCategoryEnum::tryFrom($request->query->get('category'));
            $clientId = $client->getId();
            $payments = $paymentService->findBy($sort, $clientId, $category, $status);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if(empty($payments)) {
            return $this->json(['error' => 'No payments found'], 404);
        }

        return $this->json($payments, 200, [], [
            'groups' => 'public-payment',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }

    #[Route('/api/me/payments/{id}', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    public function get(int $id, PaymentRepository $repo, #[CurrentUser] ?Client $client): JsonResponse
    {
        $payment = $repo->findOneBy([
            'id' => $id,
            'client' => $client
        ]);
        if(is_null($payment)) {
            return $this->json(['error' => 'Payment not found'], 404);
        }

        return $this->json($payment, 200, [], [
            'groups' => 'public-payment',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }
}
