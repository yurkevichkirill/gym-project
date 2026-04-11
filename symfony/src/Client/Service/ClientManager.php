<?php

declare(strict_types=1);

namespace App\Client\Service;

use App\Booking\Repository\BookingRepository;
use App\Client\DTO\AdminUpdateClientRequest;
use App\Client\DTO\CreateClientRequest;
use App\Client\DTO\UpdateClientRequest;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Membership\Repository\MembershipRepository;
use App\Payment\Repository\PaymentRepository;
use App\RefreshToken\Repository\RefreshTokenRepository;
use App\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ClientManager
{
    public function __construct(
        private ClientRepository $clientRepo,
        private BookingRepository $bookingRepo,
        private MembershipRepository $membershipRepo,
        private RefreshTokenRepository $refreshTokenRepo,
        private UserPasswordHasherInterface $passwordHasher,
        private AvailabilityService $availabilityService,
        private EntityManagerInterface $entityManager,
    )
    {}

    public function create(CreateClientRequest $dto): Client
    {
        $client = new Client();
        $client->setFirstName($dto->firstName);
        $client->setLastName($dto->lastName);
        $client->setEmail($dto->email);
        $client->setPhone($dto->phone);
        $client->setAge($dto->age);

        $plaintextPassword = $dto->password;
        $hashedPassword = $this->passwordHasher->hashPassword(
            $client,
            $plaintextPassword
        );
        $client->setPassword($hashedPassword);

        $this->clientRepo->create($client);

        $this->entityManager->flush();

        return $client;
    }

    public function update(Client $client, UpdateClientRequest $requestDto): Client
    {
        $this->availabilityService->ensureNotBlocked($client);

        if ($requestDto->phone !== null) {
            $client->setPhone($requestDto->phone);
        }

        $this->entityManager->flush();

        return $client;
    }

    public function updateByAdmin(Client $client, AdminUpdateClientRequest $requestDto): Client
    {
        if ($requestDto->firstName !== null) {
            $client->setFirstName($requestDto->firstName);
        }

        if ($requestDto->lastName !== null) {
            $client->setLastName($requestDto->lastName);
        }

        if ($requestDto->email !== null) {
            $client->setEmail($requestDto->email);
        }

        if ($requestDto->phone !== null) {
            $client->setPhone($requestDto->phone);
        }

        if ($requestDto->age !== null) {
            $client->setAge($requestDto->age);
        }

        if ($requestDto->balance !== null) {
            $client->setBalance($requestDto->balance);
        }

        if ($requestDto->password !== null) {
            $hashed = $this->passwordHasher->hashPassword($client, $requestDto->password);
            $client->setPassword($hashed);
        }

        $this->entityManager->flush();

        return $client;
    }

    public function softDelete(Client $client, ?User $admin = null): void
    {
        if ($admin !== null && $admin->getId() === $client->getId()) {
            throw new AccessDeniedHttpException('You cannot delete yourself');
        }

        if ($client->getDeletedAt()) {
            throw new ConflictHttpException("Client already deleted");
        }

        foreach ($client->getMemberships() as $membership) {
            $this->membershipRepo->remove($membership);
        }
        foreach ($client->getBookings() as $booking) {
            $this->bookingRepo->remove($booking);
        }

        $this->clientRepo->remove($client);

        $this->refreshTokenRepo->removeAllByUser($client);

        $this->entityManager->flush();
    }

    public function restore(Client $client): Client
    {
        $client->setDeletedAt();

        $this->entityManager->flush();

        return $client;
    }

    public function block(User $admin, Client $client): Client
    {
        if ($admin->getId() === $client->getId()) {
            throw new AccessDeniedHttpException('You cannot block yourself');
        }

        if ($client->getBlockedAt()) {
            throw new ConflictHttpException('User already blocked');
        }

        $client->setBlockedAt(new DateTimeImmutable());

        $this->entityManager->flush();

        return $client;
    }

    public function unblock(Client $client): Client
    {
        $client->setBlockedAt(null);
        $this->entityManager->flush();

        return $client;
    }
}
