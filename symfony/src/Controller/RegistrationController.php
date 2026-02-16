<?php

namespace App\Controller;

use App\Client\DTO\CreateClientRequest;
use App\Client\Entity\Client;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Repository\ClientRepository;
use App\Client\Service\ClientManager;
use App\Response\OkResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use OpenApi\Attributes as OA;

final class RegistrationController extends AbstractController
{
    #[Route('/api/client/registration', methods: ['POST'])]
    #[OA\Tag(name: "Registration")]
    #[OA\RequestBody(content: new Model(type: CreateClientRequest::class))]
    public function register(
        #[MapRequestPayload] CreateClientRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
        UserPasswordHasherInterface $passwordHasher,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->create($requestDto, $passwordHasher));

        return new OkResponse(
            data: $responseDto,
            status: 201,
        );

//        $content = $request->getContent();
//
//        try {
//            $client = $serializer->deserialize($content, Client::class, "json");
//        } catch(Throwable $e) {
//            return $this->json(['error' => $e->getMessage()], 400);
//        }
//
//        $plaintextPassword = $client->getPassword();
//        $hashedPassword = $passwordHasher->hashPassword(
//            $client,
//            $plaintextPassword
//        );
//        $client->setPassword($hashedPassword);
//
//        $errors = $validator->validate($client);
//
//        if (count($errors) > 0) {
//            $errorMessages = [];
//            foreach ($errors as $error) {
//                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
//            }
//
//            return $this->json(['errors' => $errorMessages], 422);
//        }
//
//        try {
//            $repo->create($client);
//        } catch (Throwable $e) {
//            return $this->json(['error' => $e->getMessage()], 400);
//        }
//
//        return $this->json($client, 201, [], [
//            'groups' => ["public-client"]
//        ]);
    }
}
