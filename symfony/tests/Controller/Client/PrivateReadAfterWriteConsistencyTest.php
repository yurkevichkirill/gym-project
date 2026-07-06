<?php

declare(strict_types=1);

namespace App\Tests\Controller\Client;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use App\TrainingType\Entity\TrainingType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class PrivateReadAfterWriteConsistencyTest extends WebTestCase
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

    public function testMembershipListReflectsFreezeImmediatelyAfterMutation(): void
    {
        [$client, $membership] = $this->persistActiveMembership();
        $membershipId = $membership->getId();
        self::assertIsInt($membershipId);

        $this->browser->loginUser($client);

        $this->browser->jsonRequest('GET', '/api/me/memberships/?status=active&page=1&limit=20');
        self::assertSame(Response::HTTP_OK, $this->browser->getResponse()->getStatusCode());
        $warmedList = $this->decodeResponse();
        self::assertSame(1, $warmedList['meta']['pagination']['total'] ?? null);
        self::assertSame('active', $this->findStatus($warmedList, $membershipId));

        $this->browser->jsonRequest('POST', sprintf('/api/me/memberships/%d/freeze/', $membershipId));
        self::assertSame(Response::HTTP_OK, $this->browser->getResponse()->getStatusCode(), (string) $this->browser->getResponse()->getContent());
        $mutationPayload = $this->decodeResponse();
        self::assertSame('frozen', $mutationPayload['data']['status'] ?? null);

        $this->browser->jsonRequest('GET', '/api/me/memberships/?page=1&limit=20');
        self::assertSame(Response::HTTP_OK, $this->browser->getResponse()->getStatusCode());
        $freshList = $this->decodeResponse();
        self::assertSame(1, $freshList['meta']['pagination']['total'] ?? null);
        self::assertSame(1, $freshList['meta']['pagination']['page'] ?? null);
        self::assertSame(20, $freshList['meta']['pagination']['limit'] ?? null);
        self::assertSame('frozen', $this->findStatus($freshList, $membershipId));
    }

    public function testBookingListReflectsCancelImmediatelyAfterMutation(): void
    {
        [$client, $booking] = $this->persistPendingBooking();
        $bookingId = $booking->getId();
        self::assertIsInt($bookingId);

        $this->browser->loginUser($client);

        $this->browser->jsonRequest('GET', '/api/me/bookings/?status=pending&page=1&limit=20');
        self::assertSame(Response::HTTP_OK, $this->browser->getResponse()->getStatusCode());
        $warmedList = $this->decodeResponse();
        self::assertSame(1, $warmedList['meta']['pagination']['total'] ?? null);
        self::assertSame('pending', $this->findStatus($warmedList, $bookingId));

        $this->browser->jsonRequest('POST', sprintf('/api/me/bookings/%d/cancel/', $bookingId));
        self::assertSame(Response::HTTP_OK, $this->browser->getResponse()->getStatusCode(), (string) $this->browser->getResponse()->getContent());
        $mutationPayload = $this->decodeResponse();
        self::assertSame('canceled_by_client', $mutationPayload['data']['status'] ?? null);

        $this->browser->jsonRequest('GET', '/api/me/bookings/?page=1&limit=20');
        self::assertSame(Response::HTTP_OK, $this->browser->getResponse()->getStatusCode());
        $freshList = $this->decodeResponse();
        self::assertSame(1, $freshList['meta']['pagination']['total'] ?? null);
        self::assertSame(1, $freshList['meta']['pagination']['page'] ?? null);
        self::assertSame(20, $freshList['meta']['pagination']['limit'] ?? null);
        self::assertSame('canceled_by_client', $this->findStatus($freshList, $bookingId));
    }

    /**
     * @return array{Client, Membership}
     */
    private function persistActiveMembership(): array
    {
        $suffix = bin2hex(random_bytes(8));

        $client = $this->createClientEntity('membership', $suffix);

        $membership = new Membership();
        $membership->setClient($client);
        $membership->setName('Read after write membership');
        $membership->setDurationDays(30);
        $membership->setSessionLimit(null);
        $payment = new Payment(PaymentMethodEnum::BALANCE);
        $payment->setClient($client);
        $payment->setAmount(1_000);
        $payment->setCategory(PaymentCategoryEnum::MEMBERSHIP);
        $payment->setStatus(PaymentStatusEnum::SUCCEEDED);
        $payment->setPaidAt(new DateTimeImmutable('-1 day'));
        $payment->setMembership($membership);
        $membership->setPayment($payment);

        $this->entityManager->persist($client);
        $this->entityManager->persist($membership);
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $membership->setStatus(MembershipStatusEnum::ACTIVE);
        $membership->setStartDate(new DateTimeImmutable('-1 day'));
        $membership->setEndDate(new DateTimeImmutable('+30 days'));
        $this->entityManager->flush();

        return [$client, $membership];
    }

    /**
     * @return array{Client, Booking}
     */
    private function persistPendingBooking(): array
    {
        $suffix = bin2hex(random_bytes(8));

        $client = $this->createClientEntity('booking', $suffix);

        $trainingType = new TrainingType();
        $trainingType->setName("Read after write training {$suffix}");
        $trainingType->setDescription('Read after write booking test');

        $trainer = new Trainer();
        $trainer->setFirstName('ReadWrite');
        $trainer->setLastName('Trainer');
        $trainer->setEmail("read_write_trainer_{$suffix}@example.com");
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

        $training = new Training();
        $training->setTrainerWorkTime($worktime);
        $training->setStartTime(new DateTimeImmutable('10:00:00'));
        $training->setDurationMinutes(60);
        $training->setIsBusy(true);

        $booking = new Booking();
        $booking->setClient($client);
        $booking->setTraining($training);
        $booking->setStatus(BookingStatusEnum::PENDING);

        $payment = new Payment(PaymentMethodEnum::CARD);
        $payment->setClient($client);
        $payment->setTrainer($trainer);
        $payment->setAmount(1_000);
        $payment->setCategory(PaymentCategoryEnum::TRAINER);
        $payment->setStatus(PaymentStatusEnum::PENDING);
        $payment->setExpiresAt(new DateTimeImmutable('+30 minutes'));
        $payment->setStripePaymentIntentId('pi_read_after_write_' . $suffix);
        $payment->setBooking($booking);
        $booking->setPayment($payment);

        $this->entityManager->persist($client);
        $this->entityManager->persist($trainingType);
        $this->entityManager->persist($trainer);
        $this->entityManager->persist($worktime);
        $this->entityManager->persist($training);
        $this->entityManager->persist($booking);
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return [$client, $booking];
    }

    private function createClientEntity(string $prefix, string $suffix): Client
    {
        $client = new Client();
        $client->setFirstName('ReadWrite');
        $client->setLastName('Client');
        $client->setEmail("read_write_{$prefix}_client_{$suffix}@example.com");
        $client->setPhone('+37529' . random_int(1_000_000, 9_999_999));
        $client->setPassword('not-used-in-test');
        $client->setAge(30);
        $client->setBalance(0);
        $client->setIsActive(true);

        return $client;
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

    /**
     * @param array<string, mixed> $payload
     */
    private function findStatus(array $payload, int $id): ?string
    {
        $items = $payload['data'] ?? null;
        self::assertIsArray($items);

        foreach ($items as $item) {
            self::assertIsArray($item);

            if (($item['id'] ?? null) === $id) {
                $status = $item['status'] ?? null;
                self::assertIsString($status);

                return $status;
            }
        }

        return null;
    }
}
