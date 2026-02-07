<?php

namespace App\Controller\User;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Membership\Entity\Membership;
use App\Membership\Repository\MembershipRepository;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class MembershipController extends AbstractController
{
    #[Route('/api/me/membership', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    public function getMy(#[CurrentUser] ?Client $client, MembershipRepository $membershipRepo): JsonResponse
    {
        $membership = $membershipRepo->findBy([
            "client" => $client
        ]);
        if(empty($membership)) {
            return $this->json(['error' => "Membership not found"], 404);
        }

        return $this->json($membership[0], 200, [], [
            'groups' => 'public-membership',
            DateTimeNormalizer::TIMEZONE_KEY => 'Europe/Minsk',
            'datetime_format' => 'Y-m-d'
        ]);
    }

    #[Route('api/me/membership', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Membership::class, groups: ['create-membership']))]
    #[IsGranted('ROLE_CLIENT')]
    public function createMy(
        #[CurrentUser] ?Client $client,
        Request $request,
        MembershipRepository $membershipRepo,
        MembershipPlanRepository $membershipPlanRepo,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        if(count($client->getMemberships()) > 0) {
            return $this->json(['error' => 'You already have membership'], 409);
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

    #[Route('api/me/membership', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: Membership::class, groups: ['update-membership']))]
    #[IsGranted('ROLE_CLIENT')]
    public function updateMy(
        #[CurrentUser] ?Client $client,
        MembershipRepository $membershipRepo,
        ClientRepository $clientRepo,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $membership = $membershipRepo->findBy([
            'client' => $client
        ]);
        if(empty($membership)) {
            return $this->json(['text' => 'You have no membership'], 200);
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

    #[Route('api/me/membership', methods: ['DELETE'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    public function deleteMy(
        #[CurrentUser] ?Client $client,
        MembershipRepository $membershipRepo,
        ClientRepository $clientRepo
    ): JsonResponse
    {
        $membership = $membershipRepo->findBy([
            'client' => $client
        ]);
        if(empty($membership)) {
            return $this->json(['text' => 'You have no membership'], 200);
        }

        try {
            $membershipRepo->remove($membership[0]);
        } catch(Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
