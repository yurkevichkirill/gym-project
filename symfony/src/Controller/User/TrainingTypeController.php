<?php

namespace App\Controller\User;

use App\TrainingType\Entity\TrainingType;
use App\TrainingType\Repository\TrainingTypeRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class TrainingTypeController extends AbstractController
{
    #[Route('api/training-types', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainingType::class, groups: ['create-update-training-type']))]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Request $request,
        TrainingTypeRepository $repo,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $json = $request->getContent();
        try {
            $trainingType = $serializer->deserialize($json, TrainingType::class, 'json');
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($trainingType);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        try {
            $repo->create($trainingType);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($trainingType, 201, [], [
            'groups' => ['public-training-type']
        ]);
    }

    #[Route('api/training-types/{id}', methods: ['PATCH', 'PUT'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainingType::class, groups: ['create-update-training-type']))]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        TrainingType $trainingType,
        Request $request,
        SerializerInterface $serializer,
        TrainingTypeRepository $repo,
        ValidatorInterface $validator
    ): JsonResponse
    {
        try {
            $serializer->deserialize($request->getContent(), TrainingType::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $trainingType
            ]);
            $repo->save();
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($trainingType);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        return $this->json($trainingType, 200, [], [
            'groups' => ['public-training-type']
        ]);
    }

    #[Route('api/training-types/{id}', methods: ['DELETE'], format: 'json')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(TrainingTypeRepository $repo, TrainingType $trainingType): JsonResponse
    {
        try {
            $repo->remove($trainingType);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
