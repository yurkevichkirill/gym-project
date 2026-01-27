<?php

namespace App\Controller;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MembershipController extends AbstractController
{
    #[Route('/api/clients/{id}/memberships/',)]
    public function index(int $id, EntityManagerInterface $em): JsonResponse
    {
        $client = $em->getRepository(Client::class)->find($id);
        if(is_null($client)) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        m''
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MembershipController.php',
        ]);
    }
}
