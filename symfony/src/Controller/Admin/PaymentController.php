<?php

namespace App\Controller\Admin;

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
    #[Route('/api/payments', methods: ['GET'], format: 'json')]
    #[Route('/api/clients/{clientId}/payments', methods: ['GET'], format: 'json')]
    #[OA\Parameter(
        name: 'status',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'category',
        in: 'query'
    )]
    #question
    #[OA\Parameter(
        name: 'clientId',
        in: 'query'
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(Request $request, PaymentServiceInterface $paymentService, ?int $clientId = null): JsonResponse
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
            $clientId = $clientId ?? $request->query->getInt('clientId');
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

    #[Route('/api/payments/{id}', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_ADMIN')]
    public function get(int $id, PaymentRepository $repo): JsonResponse
    {
        $payment = $repo->find($id);
        if(is_null($payment)) {
            return $this->json(['error' => 'Payment not found'], 404);
        }

        return $this->json($payment, 200, [], [
            'groups' => 'public-payment',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }

    #[Route('/api/payments', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Payment::class, groups: ['create-payment']))]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Request $request,
        PaymentRepository $paymentRepo,
        ClientRepository $clientRepo,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PaymentServiceInterface $paymentService
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $client = $clientRepo->find($data['client']['id']);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        try {
            $payment = $serializer->deserialize($request->getContent(), Payment::class, 'json');
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $payment->setClient($client);

        $paymentService->pay($client, $payment);

        $errors = $validator->validate($payment);
        if(count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        try {
            $paymentRepo->create($payment);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($payment, 201, [], [
            'groups' => 'public-payment',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }

    #[Route('/api/payments/{id}', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Payment::class, groups: ['update-payment']))]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Payment $payment,
        Request $request,
        ValidatorInterface $validator,
        PaymentRepository $paymentRepo,
        ClientRepository $clientRepo,
        SerializerInterface $serializer
    ):JsonResponse
    {
        try {
            $serializer->deserialize($request->getContent(), Payment::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $payment
            ]);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['client']['id'])) {
            $client = $clientRepo->find($data['client']['id']);

            if (is_null($client)) {
                return $this->json(['error' => 'Client not found'], 404);
            }

            $payment->setClient($client);
        }

        $errors = $validator->validate($payment);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        try {
            $paymentRepo->save();
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($payment, 200, [], [
            'groups' => 'public-payment',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }

    #[Route('api/payments/{id}', methods: ['DELETE'], format: 'json')]
    #[IsGranted('ROLE_ADMIN')]
    public function remove(int $id, PaymentRepository $repo): JsonResponse
    {
        $payment = $repo->find($id);
        if(is_null($payment)) {
            return $this->json(['error' => 'Payment not found']);
        }

        try {
            $repo->remove($payment);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
