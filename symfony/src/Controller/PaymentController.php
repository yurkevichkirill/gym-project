<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Payment;
use App\Service\PaymentServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class PaymentController extends AbstractController
{
    #[Route('/api/payments', methods: ['GET'], format: 'json')]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $payments = $em->getRepository(Payment::class)->findAll();

        return $this->json($payments, 200, [], [
            'groups' => 'public-payment',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }

    #[Route('/api/clients/{id}/payments', methods: ['GET'], format: 'json')]
    public function indexClient(EntityManagerInterface $em, int $id): JsonResponse
    {
        $client = $em->getRepository(Client::class)->find($id);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $payments = $em->getRepository(Payment::class)->findBy([
            "client" => $client
        ]);
        if(empty($payments)) {
            return $this->json(['error' => "Client has no payments"], 404);
        }

        return $this->json($payments, 200, [], [
            'groups' => 'public-payment',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d H:i:s'
        ]);
    }

    #[Route('/api/payments/{id}', methods: ['GET'], format: 'json')]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $payment = $em->getRepository(Payment::class)->find($id);
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
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PaymentServiceInterface $paymentService
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $client = $em->getRepository(Client::class)->find($data['client']['id']);
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

        $em->persist($payment);
        try {
            $em->flush();
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
    public function update(
        Payment $payment,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $em,
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
            $client = $em->getRepository(Client::class)->find($data['client']['id']);

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
            $em->flush();
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
    public function remove(int $id, EntityManagerInterface $em): JsonResponse
    {
        $payment = $em->getRepository(Payment::class)->find($id);
        if(is_null($payment)) {
            return $this->json(['error' => 'Payment not found']);
        }

        $em->remove($payment);
        $em->flush();

        return $this->json(null, 204);
    }
}
