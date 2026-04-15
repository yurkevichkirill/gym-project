<?php

namespace App\Controller\Admin;

use App\Response\OkResponse;
use App\TrainingType\DTO\CreateTrainingTypeRequest;
use App\TrainingType\DTO\UpdateTrainingTypeRequest;
use App\TrainingType\Entity\TrainingType;
use App\TrainingType\Mapper\TrainingTypeMapperInterface;
use App\TrainingType\Service\TrainingTypeManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TrainingTypeController extends AbstractController
{
    #[Route('/api/training/types/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: CreateTrainingTypeRequest::class))]
    #[OA\Tag(name: "Admin: TrainingType")]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload] CreateTrainingTypeRequest $requestDto,
        TrainingTypeManager $manager,
        TrainingTypeMapperInterface $mapper,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->create($requestDto));

        return new OkResponse(
            data: $responseDto,
            status: 201,
        );
    }

    #[Route('/api/training/types/{id}/', methods: ['PATCH', 'PUT'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: UpdateTrainingTypeRequest::class))]
    #[OA\Tag(name: "Admin: TrainingType")]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        #[MapRequestPayload] UpdateTrainingTypeRequest $requestDto,
        TrainingType $trainingType,
        TrainingTypeManager $manager,
        TrainingTypeMapperInterface $mapper,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->update($requestDto, $trainingType));

        return new OkResponse(
            data: $responseDto,
            status: 201,
        );
    }

    #[Route('/api/training/types/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Admin: TrainingType")]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        TrainingType $trainingType,
        TrainingTypeManager $manager,
    ): Response
    {
        $manager->remove($trainingType);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
