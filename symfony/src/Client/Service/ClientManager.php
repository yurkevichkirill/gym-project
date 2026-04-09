<?php

declare(strict_types=1);

namespace App\Client\Service;

use App\Booking\Entity\Booking;
use App\Booking\Repository\BookingRepository;
use App\Client\DTO\AdminUpdateClientRequest;
use App\Client\DTO\CreateClientRequest;
use App\Client\DTO\UpdateClientRequest;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Exception\InsufficientFundsException;
use App\Membership\Entity\Membership;
use App\Membership\Repository\MembershipRepository;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Repository\PaymentRepository;
use App\RefreshToken\Repository\RefreshTokenRepository;
use App\Trainer\Entity\Trainer;
use App\User\Entity\User;
use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ClientManager
{
    public function __construct(
        private ClientRepository $clientRepo,
        private BookingRepository $bookingRepo,
        private PaymentRepository $paymentRepo,
        private MembershipRepository $membershipRepo,
        private RefreshTokenRepository $refreshTokenRepo,
        private UserPasswordHasherInterface $passwordHasher,
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

        return $client;
    }

    public function update(Client $client, UpdateClientRequest $requestDto): Client
    {
        if ($requestDto->phone !== null) {
            $client->setPhone($requestDto->phone);
        }

        $this->clientRepo->save();

        return $client;
    }

    public function adminUpdate(Client $client, AdminUpdateClientRequest $requestDto): Client
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

        $this->clientRepo->save();

        return $client;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function softDelete(Client $client): void {
        foreach ($client->getMemberships() as $membership) {
            $this->membershipRepo->remove($membership);
        }
        foreach ($client->getBookings() as $booking) {
            $this->bookingRepo->remove($booking);
        }

        $this->clientRepo->remove($client);
    }

    /**
     * @throws DateMalformedIntervalStringException
     * @throws DateMalformedStringException
     */
    public function isClientAvailableInDate(Client $client, DateTimeImmutable $date, string $startTime, int $durationMinutes, string $oldStartTime): bool
    {
        $clientBusy = $this->getClientBusy($client, $date);
        $clientBusyWithoutCurrent = array_filter($clientBusy, fn ($slot) => $slot['start'] !== $oldStartTime);
        $endTime = new DateTimeImmutable($startTime)->add(new DateInterval("PT" . $durationMinutes . "M"))->format('H:i:s');

        foreach ($clientBusyWithoutCurrent as $trainingSlot) {
            if(
                $startTime >= $trainingSlot['start'] && $startTime < $trainingSlot['end'] ||
                $endTime > $trainingSlot['start'] && $startTime <= $trainingSlot['end']
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws DateMalformedIntervalStringException
     */
    public function getClientBusy(Client $client, DateTimeImmutable $date): array
    {
        $bookings = $this->bookingRepo->getClientBookingsByDate($client, $date);

        $clientBusy = [];
        foreach ($bookings as $booking) {
            $clientBusy[] = [
                'start' => $booking->getTraining()->getStartTime()->format('H:i:s'),
                'end' => $booking->getTraining()->getStartTime()->add(new DateInterval("PT" . $booking->getTraining()->getDurationMinutes() . "M"))->format('H:i:s')
            ];
        }

        return $clientBusy;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function pay(Client $client, float $price, ?Trainer $trainer = null): Payment
    {
        $balance = (float) $client->getBalance();
        if (!$this->hasClientEnoughMoney($balance, $price)) {
            throw new InsufficientFundsException();
        }

        $payment = new Payment();
        $payment->setClient($client);
        $payment->setAmount((string) $price);
        $payment->setIsRefund(false);
        if ($trainer) {
            $payment->setTrainer($trainer);
            $payment->setCategory(PaymentCategoryEnum::TRAINER);
        } else {
            $payment->setCategory(PaymentCategoryEnum::MEMBERSHIP);
        }
        $this->paymentRepo->create($payment);

        $client->setBalance((string) ($balance - $price));

        return $payment;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function refund(Client $client, Payment $payment): void
    {
        $balance = (float) $client->getBalance();

        $paymentRefund = new Payment();
        $paymentRefund->setClient($client);
        $paymentRefund->setAmount($payment->getAmount());
        $paymentRefund->setIsRefund(true);

        $paymentRefund->setTrainer($payment->getTrainer());
        $paymentRefund->setCategory($payment->getCategory());

        $this->paymentRepo->create($paymentRefund);

        $client->setBalance((string) ($balance + $payment->getAmount()));
    }

    private function hasClientEnoughMoney(float $balance, float $price): bool
    {
        return $balance >= $price;
    }

    public function block(Client $client): Client
    {
        $client->setBlockedAt(new DateTimeImmutable());
        $this->clientRepo->save();
        $this->refreshTokenRepo->removeAllByUser($client);

        return $client;
    }

    public function unblock(Client $client): Client
    {
        $client->setBlockedAt(null);
        $this->clientRepo->save();

        return $client;
    }
}
