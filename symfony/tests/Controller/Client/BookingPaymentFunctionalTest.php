<?php

declare(strict_types=1);

namespace App\Tests\Controller\Client;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainingType\Entity\TrainingType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class BookingPaymentFunctionalTest extends WebTestCase
{
    private KernelBrowser $browser;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->browser = self::createClient();
        $this->browser->disableReboot();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->getConnection()->rollBack();
        }

        $this->entityManager->close();

        parent::tearDown();
    }

    public function testBookingPaidFromBalanceIsSettledAtomically(): void
    {
        [$client, $trainer, $worktime] = $this->persistBookingPrerequisites(clientBalance: 5_000);

        $this->browser->loginUser($client);
        $this->requestBooking($trainer, $worktime);

        $response = $this->browser->getResponse();
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode(), (string) $response->getContent());

        $payload = $this->decodeResponse();
        self::assertSame('scheduled', $payload['data']['status'] ?? null);
        self::assertSame('balance', $payload['data']['payment']['method'] ?? null);
        self::assertSame('succeeded', $payload['data']['payment']['status'] ?? null);

        $bookingId = $payload['data']['id'] ?? null;
        self::assertIsInt($bookingId);

        $this->entityManager->clear();

        $booking = $this->entityManager->find(Booking::class, $bookingId);
        self::assertInstanceOf(Booking::class, $booking);
        self::assertSame(BookingStatusEnum::SCHEDULED, $booking->getStatus());

        $payment = $booking->getPayment();
        self::assertInstanceOf(Payment::class, $payment);
        self::assertSame(PaymentMethodEnum::BALANCE, $payment->getMethod());
        self::assertSame(PaymentStatusEnum::SUCCEEDED, $payment->getStatus());
        self::assertNotNull($payment->getPaidAt());

        $persistedClient = $this->entityManager->find(Client::class, $client->getId());
        $persistedTrainer = $this->entityManager->find(Trainer::class, $trainer->getId());
        self::assertInstanceOf(Client::class, $persistedClient);
        self::assertInstanceOf(Trainer::class, $persistedTrainer);
        self::assertSame(4_000, $persistedClient->getBalance());
        self::assertSame(1_000, $persistedTrainer->getBalance());
    }

    public function testBookingPaidByCardRemainsPendingUntilWebhook(): void
    {
        [$client, $trainer, $worktime] = $this->persistBookingPrerequisites(clientBalance: 500);

        $this->browser->loginUser($client);
        $this->requestBooking($trainer, $worktime);

        $response = $this->browser->getResponse();
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode(), (string) $response->getContent());

        $payload = $this->decodeResponse();
        self::assertSame('pending', $payload['data']['status'] ?? null);
        self::assertSame('card', $payload['data']['payment']['method'] ?? null);
        self::assertSame('pending', $payload['data']['payment']['status'] ?? null);

        $bookingId = $payload['data']['id'] ?? null;
        self::assertIsInt($bookingId);

        $this->entityManager->clear();

        $booking = $this->entityManager->find(Booking::class, $bookingId);
        self::assertInstanceOf(Booking::class, $booking);
        self::assertSame(BookingStatusEnum::PENDING, $booking->getStatus());

        $payment = $booking->getPayment();
        self::assertInstanceOf(Payment::class, $payment);
        self::assertSame(PaymentMethodEnum::CARD, $payment->getMethod());
        self::assertSame(PaymentStatusEnum::PENDING, $payment->getStatus());
        self::assertNull($payment->getPaidAt());

        $persistedClient = $this->entityManager->find(Client::class, $client->getId());
        $persistedTrainer = $this->entityManager->find(Trainer::class, $trainer->getId());
        self::assertInstanceOf(Client::class, $persistedClient);
        self::assertInstanceOf(Trainer::class, $persistedTrainer);
        self::assertSame(500, $persistedClient->getBalance());
        self::assertSame(0, $persistedTrainer->getBalance());
    }

    /**
     * @return array{Client, Trainer, TrainerWorkTime}
     */
    private function persistBookingPrerequisites(int $clientBalance): array
    {
        $suffix = bin2hex(random_bytes(8));

        $client = new Client();
        $client->setFirstName('Functional');
        $client->setLastName('Client');
        $client->setEmail("booking_client_{$suffix}@example.com");
        $client->setPhone('+37529' . random_int(1_000_000, 9_999_999));
        $client->setPassword('not-used-in-test');
        $client->setAge(30);
        $client->setBalance($clientBalance);
        $client->setIsActive(true);

        $trainingType = new TrainingType();
        $trainingType->setName("Functional booking {$suffix}");
        $trainingType->setDescription('Functional booking test');

        $trainer = new Trainer();
        $trainer->setFirstName('Functional');
        $trainer->setLastName('Trainer');
        $trainer->setEmail("booking_trainer_{$suffix}@example.com");
        $trainer->setPhone('+37533' . random_int(1_000_000, 9_999_999));
        $trainer->setPassword('not-used-in-test');
        $trainer->setPricePerHour(1_000);
        $trainer->setTrainingType($trainingType);
        $trainer->setIsActive(true);

        $worktime = new TrainerWorkTime();
        $worktime->setTrainer($trainer);
        $worktime->setDate(new DateTimeImmutable('+2 days'));
        $worktime->setStartTime(new DateTimeImmutable('09:00:00'));
        $worktime->setEndTime(new DateTimeImmutable('18:00:00'));

        $membership = new Membership();
        $membership->setClient($client);
        $membership->setName('Functional membership');
        $membership->setDurationDays(30);
        $membership->setSessionLimit(null);

        $this->entityManager->persist($client);
        $this->entityManager->persist($trainingType);
        $this->entityManager->persist($trainer);
        $this->entityManager->persist($worktime);
        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        $membership->setStatus(MembershipStatusEnum::ACTIVE);
        $membership->setStartDate(new DateTimeImmutable('-1 day'));
        $membership->setEndDate(new DateTimeImmutable('+30 days'));
        $this->entityManager->flush();

        return [$client, $trainer, $worktime];
    }

    private function requestBooking(Trainer $trainer, TrainerWorkTime $worktime): void
    {
        $trainerId = $trainer->getId();
        self::assertIsInt($trainerId);

        $this->browser->jsonRequest('POST', '/api/me/bookings/', [
            'durationMinutes' => 60,
            'startTime' => '10:00:00',
            'trainerId' => $trainerId,
            'date' => $worktime->getDate()->format('Y-m-d'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(): array
    {
        $content = $this->browser->getResponse()->getContent();
        self::assertIsString($content);

        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
