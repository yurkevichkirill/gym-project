<?php

namespace App\Controller\Admin;

use App\Response\OkResponse;
use App\Trainer\DTO\CreateTrainerRequest;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Repository\TrainerRepository;
use App\Trainer\Service\TrainerManager;
use App\TrainingType\Repository\TrainingTypeRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class TrainerController extends AbstractController
{
    #[Route('/api/trainers/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: CreateTrainerRequest::class))]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload] CreateTrainerRequest $requestDto,
        TrainerMapperInterface $mapper,
        TrainerManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->create($requestDto), true);

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainers/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Trainer::class, groups: ['create-update-trainer']))]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Trainer $trainer,
        Request $request,
        ValidatorInterface $validator,
        TrainerRepository $trainerRepo,
        TrainingTypeRepository $trainingTypeRepo,
        SerializerInterface $serializer
    ): JsonResponse
    {
        try {
            $serializer->deserialize($request->getContent(), Trainer::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $trainer
            ]);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['trainingType']['id'])) {
            $trainingType = $trainingTypeRepo->find($data['trainingType']['id']);

            if (is_null($trainingType)) {
                return $this->json(['error' => 'Training type not found'], 404);
            }

            $trainer->setTrainingType($trainingType);
        }

        $errors = $validator->validate($trainer);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        try {
            $trainerRepo->save();
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($trainer, 200, [], [
            'groups' => 'public-trainer',
        ]);
    }

    #[Route('api/trainers/{id}', methods: ['DELETE'], format: 'json')]
    #[IsGranted('ROLE_ADMIN')]
    public function remove(Trainer $trainer, TrainerRepository $repo): JsonResponse
    {
        try {
            $repo->remove($trainer);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
