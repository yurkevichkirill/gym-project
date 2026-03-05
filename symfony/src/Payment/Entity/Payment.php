<?php

namespace App\Payment\Entity;

use App\Client\Entity\Client;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

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

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::ENUM)]
    private ?PaymentCategoryEnum $category = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

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
        if (!$trainer) {
            $this->trainerFullName = $trainer->getFirstName() . " " . $trainer->getLastName();
        }

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

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

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function initializeDefaults(): void
    {
        $this->paidAt = new DateTimeImmutable();
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

    public function getTrainerFullName(): ?string
    {
        return $this->trainerFullName;
    }
}
