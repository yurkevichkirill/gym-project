<?php

namespace App\Controller\Admin;

use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\TrainerWorkTime\Service\TrainerWorkTimeServiceInterface;
use DateTimeImmutable;
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

final class TrainerWorkTimeController extends AbstractController
{
    #[Route('api/trainers/{id}/work-time', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainerWorkTime::class, groups: ['create-update-trainer-worktime']))]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        int                       $id,
        TrainerWorkTimeRepository $worktimeRepo,
        TrainerRepository         $trainerRepo,
        Request                   $request,
        SerializerInterface       $serializer,
        ValidatorInterface        $validator
    ): JsonResponse
    {
        try {
            $trainerAvailability = $serializer->deserialize($request->getContent(), TrainerWorkTime::class, 'json');
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $trainer = $trainerRepo->find($id);
        if(is_null($trainer)) {
            return $this->json(['error' => 'OurTrainer not found'], 404);
        }

        $trainerAvailability->setTrainer($trainer);

        $errors = $validator->validate($trainerAvailability);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        try {
            $worktimeRepo->create($trainerAvailability);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($trainerAvailability, 201, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-trainer-worktime']
        ]);
    }

    #change route
    #[Route('api/work-time/{id}', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainerWorkTime::class, groups: ['create-update-trainer-worktime']))]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        int $id,
        TrainerWorkTimeRepository $worktimeRepo,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $worktime = $worktimeRepo->find($id);
        if(is_null($worktime)) {
            return $this->json(['error' => 'OurTrainer work time not found'], 404);
        }

        try {
            $serializer->deserialize($request->getContent(), TrainerWorkTime::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $worktime
            ]);
            $worktimeRepo->save();
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($worktime);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        return $this->json($worktime, 200, [], [
            'datetime_format' => 'H:i',
            'groups' => ['public-trainer-worktime']
        ]);
    }

    #change route
    #[Route('api/work-time/{id}', methods: ['DELETE'], format: 'json')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        int $id,
        TrainerWorkTimeRepository $worktimeRepo
    ): JsonResponse
    {
        $worktime = $worktimeRepo->find($id);
        if(is_null($worktime)) {
            return $this->json(['error' => 'OurTrainer work time not found'], 404);
        }

        try {
            $worktimeRepo->remove($worktime);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
