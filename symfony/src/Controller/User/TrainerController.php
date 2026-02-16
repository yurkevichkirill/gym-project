<?php

namespace App\Controller\User;

use App\Booking\Enum\BookingStatusEnum;
use App\Response\OkResponse;
use App\Trainer\DTO\GetTypesTrainers;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Query\TrainersQuery;
use App\Trainer\Repository\TrainerRepository;
use App\Trainer\Service\TrainerServiceInterface;
use App\TrainingType\Repository\TrainingTypeRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class TrainerController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('api/trainers', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'trainingTypeId', in: 'query', example: 1)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'createdAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Trainers")]
    public function getAll(
        Request $request,
        TrainerMapperInterface $mapper,
        TrainersQuery $handler,
        TrainerRepository $trainerRepo,
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'createdAt:ASC');
        $trainingTypeId = $request->query->get('trainingTypeId') ? (int) $request->query->get('trainingTypeId') : null;
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetTypesTrainers($trainingTypeId, $sortRaw, $page, $limit);

        $trainers = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($trainer) => $mapper->map($trainer), $trainers),
            $queryDto->page,
            $queryDto->limit,
            $trainerRepo->count(),
            $queryDto->sort,
            200,
        );
    }

    #[Route('api/trainers/{id}', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Trainers")]
    public function get(Trainer $trainer, TrainerMapperInterface $mapper): OkResponse
    {
        return new OkResponse(
            data: $mapper->map($trainer),
            status: 200,
        );
    }
}
