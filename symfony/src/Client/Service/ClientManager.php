<?php

declare(strict_types=1);

namespace App\Client\Service;

use App\Booking\Repository\BookingRepository;
use App\Client\DTO\CreateClientRequest;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ClientManager
{
    public function __construct(
        private ClientRepository $clientRepo,
        private BookingRepository $bookingRepo,
    )
    {}

    public function create(CreateClientRequest $dto, UserPasswordHasherInterface $passwordHasher): Client
    {
        $client = new Client();
        $client->setFirstName($dto->firstName);
        $client->setLastName($dto->lastName);
        $client->setEmail($dto->email);
        $client->setPhone($dto->phone);
        $client->setAge($dto->age);

        $plaintextPassword = $dto->password;
        $hashedPassword = $passwordHasher->hashPassword(
            $client,
            $plaintextPassword
        );
        $client->setPassword($hashedPassword);

        $this->clientRepo->create($client);

        return $client;
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
}
