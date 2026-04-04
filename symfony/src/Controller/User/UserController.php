<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Client\Entity\Client;
use App\Client\Mapper\ClientMapperInterface;
use App\Response\OkResponse;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use OpenApi\Attributes as OA;

final class UserController extends AbstractController
{
    #[Route('/api/me/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Client: Client")]
    public function get(
        #[CurrentUser] User $user,
        ClientMapperInterface $clientMapper,
        TrainerMapperInterface $trainerMapper,
    ): OkResponse
    {
        if ($user instanceof Client) {
            $responseDto = $clientMapper->map($user);
        } else if ($user instanceof Trainer) {
            $responseDto = $trainerMapper->map($user);
        } else {
            throw new AccessDeniedHttpException('Client type not supported');
        }

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
