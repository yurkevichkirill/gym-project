<?php

declare(strict_types=1);

namespace App\Controller\Authentication;

use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use App\User\DTO\CurrentUserResponseDTO;
use App\User\Entity\User;
use App\User\Enum\UserRolesEnum;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CurrentUserController extends AbstractController
{
    #[Route('/api/auth/me/', name: 'app_auth_me', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'authMe',
        summary: 'Get the identity of the current authenticated user.',
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current authenticated user identity.',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: CurrentUserResponseDTO::class),
                                ),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized.',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
            ),
            new OA\Response(
                response: 403,
                description: 'Authenticated user is not allowed to access the endpoint.',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
            ),
        ],
    )]
    #[IsGranted(UserRolesEnum::ROLE_USER->value)]
    public function __invoke(#[CurrentUser] User $user): ItemResponse
    {
        return new ItemResponse(
            data: CurrentUserResponseDTO::fromEntity($user),
            status: Response::HTTP_OK,
        );
    }
}
