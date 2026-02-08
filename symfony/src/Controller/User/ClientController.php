<?php

namespace App\Controller\User;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Client\Service\ClientServiceInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use function PHPUnit\Framework\arrayHasKey;

final class ClientController extends AbstractController
{
    #[Route('/api/me', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    public function get(#[CurrentUser] ?Client $client): JsonResponse
    {
        return $this->json($client, 200, [], [
            'groups' => ["public-client"]
        ]);
    }

    #[Route('api/me', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Client::class, groups: ['create-update-client']))]
    #[IsGranted('ROLE_CLIENT')]
    public function update(
        Request $request,
        #[CurrentUser] ?Client $client,
        SerializerInterface $serializer,
        ClientRepository $repo,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $forbiddenFields = ['age', 'email', 'password', 'balance'];
        $data = json_decode($request->getContent(), true);
        foreach ($forbiddenFields as $field) {
            if(isset($data[$field])) {
                return $this->json(['error' => "Field $field cannot be updated"], 422);
            }
        }

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

    #[Route('api/me', methods: ['DELETE'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    public function delete(ClientRepository $repo, #[CurrentUser] ?Client $client): JsonResponse
    {
        try {
            $repo->remove($client);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
