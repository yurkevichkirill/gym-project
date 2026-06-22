<?php

declare(strict_types=1);

namespace App\Client\Service;

use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Repository\BookingRepository;
use App\Client\DTO\AdminUpdateClientRequestDTO;
use App\Client\DTO\ClientActivateRequestDTO;
use App\Client\DTO\CreateClientRequestDTO;
use App\Client\DTO\TopUpBalanceRequestDTO;
use App\Client\DTO\UpdateClientRequestDTO;
use App\Client\Entity\Client;
use App\Client\Exception\CannotDeleteClientException;
use App\Client\Repository\ClientRepository;
use App\Membership\Entity\Membership;
use App\Membership\Exception\NoActiveMembershipException;
use App\Membership\Service\VisitingService;
use App\Payment\Entity\Payment;
use App\Payment\Service\PaymentSettlementService;
use App\RefreshToken\Repository\RefreshTokenRepository;
use App\User\Exception\UserAlreadyBlockedException;
use App\User\Exception\UserAlreadyDeletedException;
use App\User\Exception\UserAlreadyExistsException;
use App\User\Exception\UserAlreadyActiveException;
use App\User\Exception\UserAlreadyNotBlockedException;
use App\User\Exception\UserNotFoundException;
use App\User\Repository\UserRepository;
use App\User\Service\AvailabilityService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ClientManager
{
    public function __construct(
        private ClientRepository $clientRepo,
        private UserRepository $userRepo,
        private BookingRepository $bookingRepo,
        private PaymentRepository $paymentRepo,
        private MembershipRepository $membershipRepo,
        private RefreshTokenRepository $refreshTokenRepo,
        private UserPasswordHasherInterface $passwordHasher,
        private AvailabilityService $userAvailabilityService,
        private VisitingService $visitingService,
        private PaymentSettlementService $paymentSettlementService,
        private EntityManagerInterface $entityManager,
    )
    {}

    public function create(CreateClientRequestDTO $dto): Client
    {
        $existingClientByEmail = $this->userRepo->findOneBy(['email' => $dto->email]);
        if ($existingClientByEmail !== null) {
            throw new UserAlreadyExistsException('Client with this email already exists.');
        }

        $existingClientByPhone = $this->userRepo->findOneBy(['phone' => $dto->phone]);
        if ($existingClientByPhone !== null) {
            throw new UserAlreadyExistsException('Client with this phone number already exists.');
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

    public function softDelete(Client $client): void
    {
        $clientId = $client->getId();

        if ($clientId === null) {
            throw new UserNotFoundException('Client not found');
        }

        $this->entityManager->wrapInTransaction(
            function () use ($clientId): void {
                $lockedClient = $this->clientRepo->findForUpdate($clientId);

                if ($lockedClient === null) {
                    throw new UserNotFoundException('Client not found');
                }

                if ($lockedClient->getDeletedAt() !== null) {
                    throw new UserAlreadyDeletedException(
                        'Client already deleted'
                    );
                }

                if ($this->bookingRepo->existsForClientInStatuses(
                    $lockedClient,
                    [
                        BookingStatusEnum::PENDING,
                        BookingStatusEnum::SCHEDULED,
                    ],
                )) {
                    throw new CannotDeleteClientException(
                        'Cannot delete client account with pending or scheduled bookings'
                    );
                }

                if ($this->paymentRepo->existsUnsettledForClient($lockedClient)) {
                    throw new CannotDeleteClientException(
                        'Cannot delete client account with unsettled payments'
                    );
                }

                if ($this->membershipRepo->findBlockingMembership($lockedClient) !== null) {
                    throw new CannotDeleteClientException(
                        'Cannot delete client account with active, frozen or pending membership'
                    );
                }

                if ($lockedClient->getBalance() !== 0) {
                    throw new CannotDeleteClientException(
                        'Cannot delete client account while balance is not zero'
                    );
                }

                $this->clientRepo->remove($lockedClient);
                $this->refreshTokenRepo->removeAllByUser($lockedClient);
            }
        );
    }

    public function restore(Client $client): Client
    {
        $client->setDeletedAt();

        $this->entityManager->flush();

        return $client;
    }

    public function block(Client $client): Client
    {
        if ($client->getBlockedAt() !== null) {
            throw new UserAlreadyBlockedException('Client already blocked');
        }

        $client->setBlockedAt(new DateTimeImmutable());

        $this->entityManager->flush();

        return $client;
    }

    public function unblock(Client $client): Client
    {
        if ($client->getBlockedAt() === null) {
            throw new UserAlreadyNotBlockedException('Client is not blocked');
        }

        $client->setBlockedAt(null);
        $this->entityManager->flush();

        return $client;
    }

    /**
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

    public function topUpBalance(
        Client $client,
        TopUpBalanceRequestDTO $requestDto,
    ): Payment {
        $clientId = $client->getId();

        if ($clientId === null) {
            throw new UserNotFoundException('Client not found');
        }

        return $this->entityManager->wrapInTransaction(
            function () use ($clientId, $requestDto): Payment {
                $lockedClient = $this->clientRepo->findForUpdate($clientId);

                if ($lockedClient === null) {
                    throw new UserNotFoundException('Client not found');
                }

                $this->userAvailabilityService->ensureNotBlocked($lockedClient);
                $this->userAvailabilityService->ensureActive($lockedClient);

                return $this->paymentSettlementService->createTopUpPayment(
                    $lockedClient,
                    $requestDto->amount,
                );
            }
        );
    }

    public function activate(ClientActivateRequestDTO $requestDto): Client
    {
        return $this->entityManager->wrapInTransaction(function () use ($requestDto) {
            $client = $this->clientRepo->findOneBy(['activationToken' => $requestDto->activationToken]);

            if ($client === null) {
                throw new UserNotFoundException('Activation token is invalid.');
            }

            $this->userAvailabilityService->ensureNotBlocked($client);
            if ($client->isActive()) {
                throw new UserAlreadyActiveException();
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
