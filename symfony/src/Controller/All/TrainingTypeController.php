<?php

namespace App\Controller\All;

use App\Response\OkResponse;
use App\TrainingType\DTO\GetTrainingTypes;
use App\TrainingType\Entity\TrainingType;
use App\TrainingType\Factory\GetTrainingTypesFactory;
use App\TrainingType\Mapper\TrainingTypeMapperInterface;
use App\TrainingType\Query\TrainingTypeQuery;
use App\TrainingType\Repository\TrainingTypeRepository;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class TrainingTypeController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/training/types/', methods: ['GET'], format: 'json')]
    #[Cache(public: true)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'name:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "All: TrainingType")]
    public function getAll(
        TrainingTypeQuery $handler,
        GetTrainingTypesFactory $factory,
        TrainingTypeMapperInterface $mapper,
        Request $request
    ): OkResponse
    {
        $queryDto = $factory->fromRequest($request);

        $trainingTypes = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($type) => $mapper->map($type), $trainingTypes),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal(),
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
