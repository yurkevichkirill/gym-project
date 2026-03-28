<?php

namespace App\Controller\All;

use App\Response\OkResponse;
use App\TrainingType\DTO\GetTrainingTypes;
use App\TrainingType\Entity\TrainingType;
use App\TrainingType\Mapper\TrainingTypeMapperInterface;
use App\TrainingType\Query\TrainingTypeQuery;
use App\TrainingType\Repository\TrainingTypeRepository;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TrainingTypeController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/training/types/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'name:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "All: TrainingType")]
    public function getAll(
        TrainingTypeQuery $handler,
        TrainingTypeRepository $trainingTypeRepo,
        TrainingTypeMapperInterface $mapper,
        Request $request
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'name:ASC');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetTrainingTypes($sortRaw, $page, $limit);

        $trainingTypes = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($type) => $mapper->map($type), $trainingTypes),
            $page,
            $limit,
            $trainingTypeRepo->count(),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/training/types/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "All: TrainingType")]
    public function get(
        TrainingType $trainingType,
        TrainingTypeMapperInterface $mapper,
    ): OkResponse
    {
        return new OkResponse(
            data: $mapper->map($trainingType),
            status: Response::HTTP_OK,
        );
    }
}
