<?php

namespace App\Client\Controller;

use App\Booking\Entity\Booking;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Client\Service\ClientServiceInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use OpenApi\Attributes as OA;

final class ClientController extends AbstractController
{
    #[Route('/api/clients', name: 'app_api_clients', methods: ['GET'], format: 'json')]
    #[OA\Parameter(
        name: 'sort',
        in: 'query'
    )]
    public function getAll(ClientServiceInterface $clientService, Request $request): JsonResponse
    {

        try {
            $sortRaw = $request->query->get('sort', 'createdAt:ASC');
            $sort = [];
            foreach (explode(',', $sortRaw) as $item) {
                [$field, $order] = explode(':',  $item);
                $sort[$field] = strtoupper($order);
            }
            $clients = $clientService->findBy($sort);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        if(empty($clients)) {
            return $this->json(['error' => 'No clients found'], 404);
        }

        return $this->json($clients, 200, [], [
            'groups' => ["public-client"]
        ]);
    }

    #[Route('/api/clients/{id}', methods: ['GET'], format: 'json')]
    public function get(Client $client): JsonResponse
    {
        return $this->json($client, 200, [], [
            'groups' => ["public-client"]
        ]);
    }

    #[Route('/api/clients', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Client::class, groups: ['create-update-client']))]
    public function create(
        Request $request,
        ClientRepository $repo,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $content = $request->getContent();

        try {
            $client = $serializer->deserialize($content, Client::class, "json");
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($client);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        try {
            $repo->create($client);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($client, 201, [], [
            'groups' => ["public-client"]
        ]);
    }

    #[Route('api/clients/{id}', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Client::class, groups: ['create-update-client']))]
    public function update(
        Request $request,
        Client $client,
        SerializerInterface $serializer,
        ClientRepository $repo,
        ValidatorInterface $validator
    ): JsonResponse
    {
        try {
            $serializer->deserialize($request->getContent(), Client::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $client
            ]);

            $errors = $validator->validate($client);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
                }

                return $this->json(['errors' => $errorMessages], 422);
            }

            $repo->save();
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }


        return $this->json($client, 200, [], [
            'groups' => ["public-client"]
        ]);
    }

    #[Route('api/clients/{id}', methods: ['DELETE'], format: 'json')]
    public function delete(ClientRepository $repo, Client $client): JsonResponse
    {
        try {
            $repo->remove($client);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
