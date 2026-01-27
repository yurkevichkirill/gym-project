<?php

namespace App\Controller;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class ClientController extends AbstractController
{
    #[Route('/api/clients', name: 'app_api_clients', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $clients = $em->getRepository(Client::class)->findAll();

        return $this->json($clients, 200, [], [
            'groups' => ["list-view"]
        ]);
    }

    #[Route('/api/clients/{id}', name: 'app_api_client', methods: ['GET'], format: 'json')]
    public function show(Client $client): JsonResponse
    {
        return $this->json($client, 200, [], [
            'groups' => ["list-view", "concrete-view"]
        ]);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/api/clients')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
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

        $em->persist($client);
        try {
            $em->flush();
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($client, 201, [], [
            'groups' => ["list-view", "concrete-view"]
        ]);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('api/clients/{id}', methods: ['PUT', 'PATCH'])]
    public function update(
        Request $request,
        Client $client,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
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

            $em->flush();
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }


        return $this->json($client, 200, [], [
            'groups' => ["list-view", "concrete-view"]
        ]);
    }

    #[Route('api/clients/{id}', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $em, Client $client): JsonResponse
    {
        $em->remove($client);

        $em->flush();

        return $this->json(null, 204);
    }
}
