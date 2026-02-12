<?php

namespace App\Controller;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use OpenApi\Attributes as OA;

final class RegistrationController extends AbstractController
{
    #[Route('/api/client/registration', methods: ['POST'])]
    #[OA\RequestBody(content: new Model(type: Client::class))]
    public function register(
        Request $request,
        ClientRepository $repo,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator): JsonResponse
    {
        $content = $request->getContent();

        try {
            $client = $serializer->deserialize($content, Client::class, "json");
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $plaintextPassword = $client->getPassword();
        $hashedPassword = $passwordHasher->hashPassword(
            $client,
            $plaintextPassword
        );
        $client->setPassword($hashedPassword);

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
}
