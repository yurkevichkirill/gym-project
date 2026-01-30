<?php

namespace App\Booking\Entity;

use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
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

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('public-booking')]
    #[Assert\NotBlank]
    private ?Training $training = null;

    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Groups('public-booking')]
    private ?DateTimeImmutable $booked_at = null;

    #[ORM\Column(
        type: Types::ENUM,
        options: ['default' => BookingStatusEnum::SCHEDULED]
    )]
    #[Groups('public-booking')]
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

    public function getTraining(): ?Training
    {
        return $this->training;
    }

    public function setTraining(Training $training): static
    {
        $this->training = $training;

        return $this;
    }

    public function getBookedAt(): ?DateTimeImmutable
    {
        return $this->booked_at;
    }

    public function setBookedAt(DateTimeImmutable $booked_at): static
    {
        $this->booked_at = $booked_at;

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

    #[ORM\PrePersist]
    public function initializeDefaults(): static
    {
        $this->booked_at = new DateTimeImmutable();
        $this->status = BookingStatusEnum::SCHEDULED;

        return $this;
    }
}
