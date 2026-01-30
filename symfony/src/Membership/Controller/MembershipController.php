<?php

namespace App\Membership\Controller;

use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\MembershipPlan\Entity\MembershipPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class MembershipController extends AbstractController
{
    #[Route('/api/clients/{id}/membership/', methods: ['GET'], format: 'json')]
    public function index(int $id, EntityManagerInterface $em): JsonResponse
    {
        $client = $em->getRepository(Client::class)->find($id);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $membership = $em->getRepository(Membership::class)->findBy([
            "client" => $client
        ]);
        if(empty($membership)) {
            return $this->json(['error' => "Client has no membership"], 404);
        }

        return $this->json($membership[0], 200, [], [
            'groups' => 'public-membership',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d'
        ]);
    }

    #[Route('api/clients/{id}/membership', methods: ['POST'], format: 'json')]
    public function create(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $client = $em->getRepository(Client::class)->find($id);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        if(count($client->getMemberships()) > 0) {
            return $this->json(['error' => 'Client already have membership'], 409);
        }

        $json = $request->getContent();
        try {
            $membership = $serializer->deserialize($json, Membership::class, 'json');
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $membership->setClient($client);
        $membershipPlanId = json_decode($json, true)['membership_plan']['id'];
        $membershipPlan = $em->getRepository(MembershipPlan::class)->find($membershipPlanId);
        if(is_null($membershipPlan)) {
            return $this->json(['error' => 'Membership type not found']);
        }

        $membership->setPlan($membershipPlan);

        $errors = $validator->validate($membership);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        $em->persist($membership);
        try {
            $em->flush();
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($membership, 201, [], [
            'groups' => 'public-membership',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d'
        ]);
    }

    #[Route('api/clients/{id}/membership', methods: ['PUT', 'PATCH'], format: 'json')]
    public function update(
        int $id,
        EntityManagerInterface $em,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $client = $em->getRepository(Client::class)->find($id);
        if(is_null($client)) {
            return $this->json(['error' => "Client not found"], 404);
        }

        $membership = $em->getRepository(Membership::class)->findBy([
            'client' => $client
        ]);
        if(empty($membership)) {
            return $this->json(['text' => 'Client has no membership'], 200);
        }

        try {
            $serializer->deserialize($request->getContent(), Membership::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $membership[0]
            ]);
            $em->flush();
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $errors = $validator->validate($membership[0]);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 422);
        }

        return $this->json($membership[0], 200, [], [
            'groups' => 'public-membership',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d'
        ]);
    }

    #[Route('api/clients/{id}/membership', methods: ['DELETE'], format: 'json')]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $client = $em->getRepository(Client::class)->find($id);
        if(is_null($client)) {
            return $this->json(['error' => "Client not found"], 404);
        }

        $membership = $em->getRepository(Membership::class)->findBy([
            'client' => $client
        ]);
        if(empty($membership)) {
            return $this->json(['text' => 'Client has no membership'], 200);
        }

        $em->remove($membership[0]);
        $em->flush();

        return $this->json(null, 204);
    }
}
