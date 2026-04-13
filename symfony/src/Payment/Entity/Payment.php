<?php

namespace App\Payment\Entity;

use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Client $client = null;

    #[ORM\OneToOne(targetEntity: Membership::class, mappedBy: 'payment')]
    private ?Membership $membership = null;

    #[ORM\OneToOne(targetEntity: Booking::class, mappedBy: 'payment')]
    private ?Booking $booking = null;

    #[ORM\Column]
    private ?bool $isRefund = null;

    #[ORM\Column(length: 200)]
    private ?string $clientFullName = null;

    #[ORM\Column(length: 180)]
    private ?string $clientEmail = null;

    #[ORM\Column(length: 20)]
    private ?string $clientPhone = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Trainer $trainer = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $trainerFullName = null;

    #[ORM\Column]
    private ?int $amount = null;

    #[ORM\Column(type: Types::ENUM)]
    private ?PaymentCategoryEnum $category = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $paidAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 10)]
    private string $currency = 'usd';

    #[ORM\Column(
        type: Types::ENUM,
    )]
    private PaymentStatusEnum $status;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $confirmedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;
        if ($client) {
            $this->clientEmail = $client->getEmail();
            $this->clientPhone = $client->getPhone();
            $this->clientFullName = $client->getFirstName() . " " . $client->getLastName();
        }

        return $this;
    }

    public function setTrainer(?Trainer $trainer): static
    {
        $this->trainer = $trainer;
        if ($trainer) {
            $this->trainerFullName = $trainer->getFirstName() . " " . $trainer->getLastName();
        }

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getIsRefund(): ?bool
    {
        return $this->isRefund;
    }

    public function setIsRefund(?bool $isRefund): static
    {
        $this->isRefund = $isRefund;

        return $this;
    }

    public function getMembership(): ?Membership
    {
        return $this->membership;
    }

    public function setMembership(?Membership $membership): static
    {
        $this->membership = $membership;

        return $this;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
    }

    public function getCategory(): ?PaymentCategoryEnum
    {
        return $this->category;
    }

    public function setCategory(PaymentCategoryEnum $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function initializeDefaults(): void
    {
        $this->createdAt = new DateTimeImmutable();

        if ($this->status === null) {
            $this->status = PaymentStatusEnum::PENDING;
        }
    }

    public function getClientFullName(): ?string
    {
        return $this->clientFullName;
    }

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function getClientPhone(): ?string
    {
        return $this->clientPhone;
    }

    public function getTrainer(): ?Trainer
    {
        return $this->trainer;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    public function getStatus(): ?PaymentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PaymentStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getConfirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?DateTimeImmutable $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTrainerFullName(): ?string
    {
        return $this->trainerFullName;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }
}
