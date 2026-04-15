<?php

namespace App\Booking\Entity;

use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Payment\Entity\Payment;
use App\Training\Entity\Training;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('public-booking')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('public-booking')]
    #[Assert\NotBlank]
    private ?Client $client = null;

    #[ORM\OneToOne(
        targetEntity: Training::class,
        inversedBy: 'booking',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    #[ORM\JoinColumn(nullable: true)]
    private ?Training $training = null;

    #[ORM\OneToOne(targetEntity: Payment::class, inversedBy: 'booking', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Payment $payment = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $bookedAt = null;

    #[ORM\Column(
        type: Types::ENUM,
        length: 50,
    )]
    private ?BookingStatusEnum $status = null;

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

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    public function getTraining(): ?Training
    {
        return $this->training;
    }

    public function setTraining(?Training $training): static
    {
        if ($this->training !== null && $this->training !== $training) {
            $this->training->setBooking(null);
        }

        $this->training = $training;

        if ($training !== null && $this !== $training->getBooking()) {
            $training->setBooking($this);
        }

        return $this;
    }

    public function getBookedAt(): ?DateTimeImmutable
    {
        return $this->bookedAt;
    }

    public function setBookedAt(DateTimeImmutable $bookedAt): static
    {
        $this->bookedAt = $bookedAt;

        return $this;
    }

    public function getStatus(): ?BookingStatusEnum
    {
        return $this->status;
    }

    public function setStatus(BookingStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function confirm(): void
    {
        $this->bookedAt = new DateTimeImmutable();

        $this->status = BookingStatusEnum::SCHEDULED;
    }

    public function cancel(BookingStatusEnum $reason): void
    {
        $this->status = $reason;

        if ($this->training) {
            $this->training = null;
        }
    }

    #[ORM\PrePersist]
    public function initializeDefaults(): static
    {
        $this->status = BookingStatusEnum::PENDING;

        return $this;
    }
}
