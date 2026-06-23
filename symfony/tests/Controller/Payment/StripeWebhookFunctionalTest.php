<?php

declare(strict_types=1);

namespace App\Tests\Controller\Payment;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
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

final class StripeWebhookFunctionalTest extends WebTestCase
{
    private const string WEBHOOK_SECRET = 'whsec_functional_test';

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

    public function testSucceededWebhookSettlesPendingBookingPayment(): void
    {
        [$booking, $payment, $trainer] = $this->persistPendingCardBooking('pi_functional_success');

        $this->requestWebhook($this->paymentIntentEvent('pi_functional_success'), validSignature: true);

        $response = $this->browser->getResponse();
        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string) $response->getContent());

        $bookingId = $booking->getId();
        $paymentId = $payment->getId();
        $trainerId = $trainer->getId();
        self::assertIsInt($bookingId);
        self::assertIsInt($paymentId);
        self::assertIsInt($trainerId);

        $this->entityManager->clear();

        $persistedBooking = $this->entityManager->find(Booking::class, $bookingId);
        $persistedPayment = $this->entityManager->find(Payment::class, $paymentId);
        $persistedTrainer = $this->entityManager->find(Trainer::class, $trainerId);
        self::assertInstanceOf(Booking::class, $persistedBooking);
        self::assertInstanceOf(Payment::class, $persistedPayment);
        self::assertInstanceOf(Trainer::class, $persistedTrainer);
        self::assertSame(BookingStatusEnum::SCHEDULED, $persistedBooking->getStatus());
        self::assertSame(PaymentStatusEnum::SUCCEEDED, $persistedPayment->getStatus());
        self::assertNotNull($persistedPayment->getPaidAt());
        self::assertNull($persistedPayment->getExpiresAt());
        self::assertSame(1_000, $persistedTrainer->getBalance());
    }

    public function testWebhookSuccessIsIdempotent(): void
    {
        [$booking, $payment, $trainer] = $this->persistPendingCardBooking('pi_functional_idempotent');
        $payload = $this->paymentIntentEvent('pi_functional_idempotent');

        $this->requestWebhook($payload, validSignature: true);
        self::assertSame(Response::HTTP_NO_CONTENT, $this->browser->getResponse()->getStatusCode());

        $this->requestWebhook($payload, validSignature: true);
        self::assertSame(Response::HTTP_NO_CONTENT, $this->browser->getResponse()->getStatusCode());

        $bookingId = $booking->getId();
        $paymentId = $payment->getId();
        $trainerId = $trainer->getId();
        self::assertIsInt($bookingId);
        self::assertIsInt($paymentId);
        self::assertIsInt($trainerId);

        $this->entityManager->clear();

        $persistedBooking = $this->entityManager->find(Booking::class, $bookingId);
        $persistedPayment = $this->entityManager->find(Payment::class, $paymentId);
        $persistedTrainer = $this->entityManager->find(Trainer::class, $trainerId);
        self::assertInstanceOf(Booking::class, $persistedBooking);
        self::assertInstanceOf(Payment::class, $persistedPayment);
        self::assertInstanceOf(Trainer::class, $persistedTrainer);
        self::assertSame(BookingStatusEnum::SCHEDULED, $persistedBooking->getStatus());
        self::assertSame(PaymentStatusEnum::SUCCEEDED, $persistedPayment->getStatus());
        self::assertSame(1_000, $persistedTrainer->getBalance());
    }

    public function testInvalidWebhookSignatureIsRejectedWithoutChangingPayment(): void
    {
        [$booking, $payment, $trainer] = $this->persistPendingCardBooking('pi_functional_invalid_signature');

        $this->requestWebhook(
            $this->paymentIntentEvent('pi_functional_invalid_signature'),
            validSignature: false,
        );

        $response = $this->browser->getResponse();
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), (string) $response->getContent());

        $bookingId = $booking->getId();
        $paymentId = $payment->getId();
        $trainerId = $trainer->getId();
        self::assertIsInt($bookingId);
        self::assertIsInt($paymentId);
        self::assertIsInt($trainerId);

        $this->entityManager->clear();

        $persistedBooking = $this->entityManager->find(Booking::class, $bookingId);
        $persistedPayment = $this->entityManager->find(Payment::class, $paymentId);
        $persistedTrainer = $this->entityManager->find(Trainer::class, $trainerId);
        self::assertInstanceOf(Booking::class, $persistedBooking);
        self::assertInstanceOf(Payment::class, $persistedPayment);
        self::assertInstanceOf(Trainer::class, $persistedTrainer);
        self::assertSame(BookingStatusEnum::PENDING, $persistedBooking->getStatus());
        self::assertSame(PaymentStatusEnum::PENDING, $persistedPayment->getStatus());
        self::assertSame(0, $persistedTrainer->getBalance());
    }

    /**
     * @return array{Booking, Payment, Trainer}
     */
    private function persistPendingCardBooking(string $intentId): array
    {
        $suffix = bin2hex(random_bytes(8));

        $client = new Client();
        $client->setFirstName('Webhook');
        $client->setLastName('Client');
        $client->setEmail("webhook_client_{$suffix}@example.com");
        $client->setPhone('+37529' . random_int(1_000_000, 9_999_999));
        $client->setPassword('not-used-in-test');
        $client->setAge(30);
        $client->setBalance(0);
        $client->setIsActive(true);

        $trainingType = new TrainingType();
        $trainingType->setName("Webhook training {$suffix}");
        $trainingType->setDescription('Stripe webhook functional test');

        $trainer = new Trainer();
        $trainer->setFirstName('Webhook');
        $trainer->setLastName('Trainer');
        $trainer->setEmail("webhook_trainer_{$suffix}@example.com");
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

        $booking = new Booking();
        $booking->setClient($client);
        $booking->setTraining($training);

        $payment = new Payment(PaymentMethodEnum::CARD);
        $payment->setClient($client);
        $payment->setTrainer($trainer);
        $payment->setAmount(1_000);
        $payment->setCategory(PaymentCategoryEnum::TRAINER);
        $payment->setStatus(PaymentStatusEnum::PENDING);
        $payment->setExpiresAt(new DateTimeImmutable('+30 minutes'));
        $payment->setStripePaymentIntentId($intentId);
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

        return [$booking, $payment, $trainer];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentIntentEvent(string $intentId): array
    {
        return [
            'id' => 'evt_' . $intentId,
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $intentId,
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $event
     */
    private function requestWebhook(array $event, bool $validSignature): void
    {
        $payload = json_encode($event, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $secret = $validSignature ? self::WEBHOOK_SECRET : 'wrong-secret';
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        $this->browser->request(
            'POST',
            '/api/webhooks/stripe/',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => sprintf('t=%d,v1=%s', $timestamp, $signature),
            ],
            content: $payload,
        );
    }
}
