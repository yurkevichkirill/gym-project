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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

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
