<?php

namespace App\Controller\Admin;

use App\Admin\Entity\Admin;
use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use App\Response\OkResponse;
use App\Trainer\DTO\AdminUpdateTrainerRequest;
use App\Trainer\DTO\CreateTrainerRequest;
use App\Trainer\Entity\Trainer;
use App\Trainer\Factory\GetTrainersFactory;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Query\TrainersQuery;
use App\Trainer\Service\TrainerManager;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TrainerController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route('/api/admin/trainers/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'minPricePerHour', in: 'query', example: 30)]
    #[OA\Parameter(name: 'maxPricePerHour', in: 'query', example: 50)]
    #[OA\Parameter(name: 'trainingTypeId', in: 'query', example: 1)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'lastName:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Trainer")]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        Request $request,
        TrainerMapperInterface $mapper,
        TrainersQuery $handler,
        GetTrainersFactory $factory,
    ): CollectionResponse
    {
        $queryDto = $factory->fromRequest($request);

        $trainers = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($trainer) => $mapper->map($trainer, true), $trainers),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/admin/trainers/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Admin: Trainer")]
    #[IsGranted('ROLE_ADMIN')]
    public function get(Trainer $trainer, TrainerMapperInterface $mapper): ItemResponse
    {
        return new ItemResponse(
            data: $mapper->map($trainer, true),
            status: Response::HTTP_OK,
        );
    }
    #[Route('/api/trainers/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: CreateTrainerRequest::class))]
    #[OA\Tag(name: "Admin: Trainer")]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload] CreateTrainerRequest $requestDto,
        TrainerMapperInterface $mapper,
        TrainerManager $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->create($requestDto), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainers/{id}/', methods: ['PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: AdminUpdateTrainerRequest::class))]
    #[OA\Tag(name: "Admin: Trainer")]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Trainer $trainer,
        #[MapRequestPayload] AdminUpdateTrainerRequest $requestDto,
        TrainerManager $manager,
        TrainerMapperInterface $mapper,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->updateByAdmin($requestDto, $trainer), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/api/trainers/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Admin: Trainer")]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        #[CurrentUser] Admin $admin,
        Trainer $trainer,
        TrainerManager $manager,
    ): NoContentResponse
    {
        $manager->softDelete($trainer, $admin);
        $this->container->get('security.token_storage')->setToken(null);
        //clean cookies in frontend

        return new NoContentResponse();
    }

    #[Route('/api/trainers/{id}/restore/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Trainer")]
    #[IsGranted('ROLE_ADMIN')]
    public function restore(
        Trainer $trainer,
        TrainerManager $manager,
        TrainerMapperInterface $mapper,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->restore($trainer), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainers/{id}/block/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Trainer")]
    #[IsGranted('ROLE_ADMIN')]
    public function block(
        #[CurrentUser] Admin $admin,
        Trainer $trainer,
        TrainerMapperInterface $mapper,
        TrainerManager $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->block($admin, $trainer), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainers/{id}/unblock/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Trainer")]
    #[IsGranted('ROLE_ADMIN')]
    public function unblock(
        Trainer $trainer,
        TrainerMapperInterface $mapper,
        TrainerManager $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->unblock($trainer), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
