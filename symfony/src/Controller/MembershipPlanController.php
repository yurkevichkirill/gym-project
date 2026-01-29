<?php

namespace App\Controller;

use App\Entity\MembershipPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class MembershipPlanController extends AbstractController
{
    #[Route('/api/memberships', methods: ['GET'], format: 'json')]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $memberships = $em->getRepository(MembershipPlan::class)->findAll();
        return $this->json($memberships, 200, [], [
            'groups' => 'public-membership-plan'
        ]);
    }

    #[Route('api/memberships/{id}', methods: ['GET'], format: 'json')]
    public function show(MembershipPlan $membershipPlan): JsonResponse
    {
        return $this->json($membershipPlan, 200, [], [
            'groups' => ['public-membership-plan']
        ]);
    }

    #[Route('api/memberships', methods: ['POST'], format: 'json')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $json = $request->getContent();
        try {
            $membershipPlan = $serializer->deserialize($json, MembershipPlan::class, 'json');
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($membershipPlan);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        $em->persist($membershipPlan);
        try {
            $em->flush();
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($membershipPlan, 201, [], [
            'groups' => ['public-membership-plan']
        ]);
    }

    #[Route('api/memberships/{id}', methods: ['PATCH', 'PUT'], format: 'json')]
    public function update(
        MembershipPlan $membershipPlan,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        try {
            $serializer->deserialize($request->getContent(), MembershipPlan::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $membershipPlan
            ]);
            $em->flush();
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($membershipPlan);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        return $this->json($membershipPlan, 200, [], [
            'groups' => ['public-membership-plan']
        ]);
    }

    #[Route('api/memberships/{id}', methods: ['DELETE'], format: 'json')]
    public function delete(EntityManagerInterface $em, MembershipPlan $membershipPlan): JsonResponse
    {
        $em->remove($membershipPlan);
        $em->flush();

        return $this->json(null, 204);
    }
}
