<?php

namespace App\Entity;

use App\Enum\TrainingStatusEnum;
use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Training $training = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Payment $payment = null;

    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeImmutable $booked_at = null;

    #[ORM\Column(
        type: Types::ENUM,
        options: ['default' => TrainingStatusEnum::SCHEDULED]
    )]
    private ?TrainingStatusEnum $status = null;

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

    public function getBookedAt(): ?\DateTimeImmutable
    {
        return $this->booked_at;
    }

    public function setBookedAt(\DateTimeImmutable $booked_at): static
    {
        $this->booked_at = $booked_at;

        return $this;
    }

    public function getStatus(): ?TrainingStatusEnum
    {
        return $this->status;
    }

    public function setStatus(TrainingStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }
}
