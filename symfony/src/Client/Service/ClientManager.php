<?php

declare(strict_types=1);

namespace App\Client\Service;

use App\Admin\Entity\Admin;
use App\Client\DTO\AdminUpdateClientRequestDTO;
use App\Client\DTO\ClientActivateRequestDTO;
use App\Client\DTO\CreateClientRequestDTO;
use App\Client\DTO\TopUpBalanceRequestDTO;
use App\Client\DTO\UpdateClientRequestDTO;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Membership\Entity\Membership;
use App\Membership\Exception\NoActiveMembershipException;
use App\Membership\Service\VisitingService;
use App\Payment\Entity\Payment;
use App\Payment\Service\PaymentSettlementService;
use App\RefreshToken\Repository\RefreshTokenRepository;
use App\User\Repository\UserRepository;
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
        private UserRepository $userRepo,
        private RefreshTokenRepository $refreshTokenRepo,
        private UserPasswordHasherInterface $passwordHasher,
        private AvailabilityService $userAvailabilityService,
        private VisitingService $visitingService,
        private PaymentSettlementService $paymentSettlementService,
        private EntityManagerInterface $entityManager,
    )
    {}

    /**
     * @throws ConflictHttpException
     */
    public function create(CreateClientRequestDTO $dto): Client
    {
        $existingClientByEmail = $this->userRepo->findOneBy(['email' => $dto->email]);
        if ($existingClientByEmail) {
            throw new ConflictHttpException('Client with this email already exists.');
        }

        $existingClientByPhone = $this->userRepo->findOneBy(['phone' => $dto->phone]);
        if ($existingClientByPhone) {
            throw new ConflictHttpException('Client with this phone number already exists.');
        }

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

    /**
     * @throws AccessDeniedHttpException
     */
    public function update(Client $client, UpdateClientRequestDTO $requestDto): Client
    {
        $this->userAvailabilityService->ensureNotBlocked($client);
        $this->userAvailabilityService->ensureActive($client);

        if ($requestDto->phone !== null) {
            $client->setPhone($requestDto->phone);
        }

        $this->entityManager->flush();

        return $client;
    }

    public function updateByAdmin(Client $client, AdminUpdateClientRequestDTO $requestDto): Client
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

        if ($requestDto->password !== null) {
            $hashed = $this->passwordHasher->hashPassword($client, $requestDto->password);
            $client->setPassword($hashed);
        }

        $this->entityManager->flush();

        return $client;
    }

    /**
     * @throws ConflictHttpException
     */
    public function softDelete(Client $client): void
    {
        if ($client->getDeletedAt()) {
            throw new ConflictHttpException('Client already deleted');
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

    /**
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     */
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

    /**
     * @throws ConflictHttpException
     */
    public function unblock(Client $client): Client
    {
        if ($client->getBlockedAt() === null) {
            throw new ConflictHttpException('Client is not blocked');
        }

        $client->setBlockedAt(null);
        $this->entityManager->flush();

        return $client;
    }

    /**
     * @throws AccessDeniedHttpException
     * @throws NoActiveMembershipException
     */
    public function visit(Client $client): Membership
    {
        $this->userAvailabilityService->ensureNotBlocked($client);
        $this->userAvailabilityService->ensureActive($client);

        $membership = $this->visitingService->visit($client);

        $this->entityManager->flush();

        return $membership;
    }

    /**
     * @throws AccessDeniedHttpException
     */
    public function topUpBalance(Client $client, TopUpBalanceRequestDTO $requestDto): Payment
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

    public function activate(ClientActivateRequestDTO $requestDto): Client
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
