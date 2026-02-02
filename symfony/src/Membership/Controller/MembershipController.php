<?php

namespace App\Membership\Controller;

use App\Client\Repository\ClientRepository;
use App\Membership\Entity\Membership;
use App\Membership\Repository\MembershipRepository;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use OpenApi\Attributes as OA;

final class MembershipController extends AbstractController
{
    #[Route('/api/clients/{id}/membership', methods: ['GET'], format: 'json')]
    public function get(int $id, MembershipRepository $membershipRepo, ClientRepository $clientRepo): JsonResponse
    {
        $client = $clientRepo->find($id);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $membership = $membershipRepo->findBy([
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
    #[OA\RequestBody(content: new Model(type: Membership::class, groups: ['create-membership']))]
    public function create(
        int $id,
        Request $request,
        MembershipRepository $membershipRepo,
        ClientRepository $clientRepo,
        MembershipPlanRepository $membershipPlanRepo,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $client = $clientRepo->find($id);
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
        $membershipPlanId = json_decode($json, true)['plan']['id'];
        $membershipPlan = $membershipPlanRepo->find($membershipPlanId);
        if(is_null($membershipPlan)) {
            return $this->json(['error' => 'Membership plan not found']);
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

        try {
            $membershipRepo->create($membership);
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
    #[OA\RequestBody(content: new Model(type: Membership::class, groups: ['update-membership']))]
    public function update(
        int $id,
        MembershipRepository $membershipRepo,
        ClientRepository $clientRepo,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $client = $clientRepo->find($id);
        if(is_null($client)) {
            return $this->json(['error' => "Client not found"], 404);
        }

        $membership = $membershipRepo->findBy([
            'client' => $client
        ]);
        if(empty($membership)) {
            return $this->json(['text' => 'Client has no membership'], 200);
        }

        try {
            $serializer->deserialize($request->getContent(), Membership::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $membership[0]
            ]);
            $membershipRepo->save();
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
    public function delete(int $id, MembershipRepository $membershipRepo, ClientRepository $clientRepo): JsonResponse
    {
        $client = $clientRepo->find($id);
        if(is_null($client)) {
            return $this->json(['error' => "Client not found"], 404);
        }

        $membership = $membershipRepo->findBy([
            'client' => $client
        ]);
        if(empty($membership)) {
            return $this->json(['text' => 'Client has no membership'], 200);
        }

        try {
            $membershipRepo->remove($membership[0]);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
