<?php

declare(strict_types=1);

namespace App\Client\Service;

use App\Admin\Entity\Admin;
use App\Client\DTO\AdminUpdateClientRequest;
use App\Client\DTO\ClientActivateRequest;
use App\Client\DTO\CreateClientRequest;
use App\Client\DTO\TopUpBalanceRequest;
use App\Client\DTO\UpdateClientRequest;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Exception\NoActiveMembershipException;
use App\Membership\Entity\Membership;
use App\Membership\Service\VisitingService;
use App\Payment\Entity\Payment;
use App\Payment\Service\PaymentSettlementService;
use App\RefreshToken\Repository\RefreshTokenRepository;
use App\User\Service\AvailabilityService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ClientManager
{
    public function __construct(
        private ClientRepository $clientRepo,
        private RefreshTokenRepository $refreshTokenRepo,
        private UserPasswordHasherInterface $passwordHasher,
        private AvailabilityService $userAvailabilityService,
        private VisitingService $visitingService,
        private PaymentSettlementService $paymentSettlementService,
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
        $this->userAvailabilityService->ensureNotBlocked($client);
        $this->userAvailabilityService->ensureActive($client);

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

    public function softDelete(Client $client, ?Admin $admin = null): void
    {
        if ($admin !== null && $admin->getId() === $client->getId()) {
            throw new AccessDeniedHttpException('You cannot delete yourself');
        }

        if ($client->getDeletedAt()) {
            throw new ConflictHttpException("Client already deleted");
        }

        $this->entityManager->wrapInTransaction(function () use ($client) {
            $this->clientRepo->remove($client);

            $this->refreshTokenRepo->removeAllByUser($client);
        });
    }

    public function restore(Client $client): Client
    {
        $client->setDeletedAt();

        $this->entityManager->flush();

        return $client;
    }

    public function block(Admin $admin, Client $client): Client
    {
        if ($admin->getId() === $client->getId()) {
            throw new AccessDeniedHttpException('You cannot block yourself');
        }

        if ($client->getBlockedAt()) {
            throw new ConflictHttpException('Client already blocked');
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

    public function visit(Client $client): Membership
    {
        $this->userAvailabilityService->ensureNotBlocked($client);
        $this->userAvailabilityService->ensureActive($client);

        if (!$this->visitingService->hasActiveMembership($client)) {
            throw new NoActiveMembershipException();
        }

        $membership = $this->visitingService->visit($client);

        $this->entityManager->flush();

        return $membership;
    }

    public function topUpBalance(Client $client, TopUpBalanceRequest $requestDto): Payment
    {
        $this->userAvailabilityService->ensureNotBlocked($client);
        $this->userAvailabilityService->ensureActive($client);

        $amount = $requestDto->amount;

        $payment = $this->paymentSettlementService->createTopUpPayment(
            $client,
            $amount,
        );

        $this->entityManager->flush();

        return $payment;
    }

    public function activate(ClientActivateRequest $requestDto): Client
    {
        return $this->entityManager->wrapInTransaction(function () use ($requestDto) {
            $client = $this->clientRepo->findOneBy(['activationToken' => $requestDto->activationToken]);

            if ($client === null) {
                throw new NotFoundHttpException('Activation token is invalid.');
            }

            $this->userAvailabilityService->ensureNotBlocked($client);
            if ($client->isActive()) {
                throw new ConflictHttpException('Account is already activated.');
            }

            $hashedPassword = $this->passwordHasher->hashPassword($client, $requestDto->password);
            $client->setPassword($hashedPassword);
            $client->setIsActive(true);
            $client->setActivationToken(null);

            $this->entityManager->flush();

            return $client;
        });
    }
}
