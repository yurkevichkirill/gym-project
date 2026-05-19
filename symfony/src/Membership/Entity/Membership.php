<?php

declare(strict_types=1);

namespace App\Membership\Entity;

use App\Client\Entity\Client;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Repository\MembershipRepository;
use App\MembershipPlan\Entity\MembershipPlan;
use App\Payment\Entity\Payment;
use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: MembershipRepository::class)]
class Membership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?MembershipPlan $plan = null;

    #[ORM\Column]
    private string $name;

    #[ORM\Column]
    private int $durationDays;

    #[ORM\Column(nullable: true)]
    private ?int $sessionLimit;

    #[ORM\OneToOne(targetEntity: Payment::class, inversedBy: 'membership', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Payment $payment = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::ENUM, length: 50)]
    private MembershipStatusEnum $status;

    #[ORM\Column(options: ['default' => 0])]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $visits = null;

    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $frozenAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVisits(): ?int
    {
        return $this->visits;
    }

    public function setVisits(int $visits): static
    {
        $this->visits = $visits;

        return $this;
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

    public function getPlan(): ?MembershipPlan
    {
        return $this->plan;
    }

    public function setPlan(?MembershipPlan $plan): static
    {
        $this->plan = $plan;

        return $this;
    }


    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDurationDays(): ?int
    {
        return $this->durationDays;
    }

    public function setDurationDays(?int $durationDays): static
    {
        $this->durationDays = $durationDays;

        return $this;
    }

    public function getSessionLimit(): ?int
    {
        return $this->sessionLimit;
    }

    public function setSessionLimit(?int $sessionLimit): static
    {
        $this->sessionLimit = $sessionLimit;

        return $this;
    }

    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getFrozenAt(): ?DateTimeImmutable
    {
        return $this->frozenAt;
    }

    public function setFrozenAt(?DateTimeImmutable $frozenAt): static
    {
        $this->frozenAt = $frozenAt;

        return $this;
    }

    public function activate(): void
    {
        $this->status = MembershipStatusEnum::ACTIVE;

        $this->startDate = new DateTimeImmutable();
        $this->endDate = $this->startDate->add(
            new DateInterval("P{$this->durationDays}D")
        );
    }

    #[ORM\PrePersist]
    public function initializeDefaults(): static
    {
        $this->createdAt = new DateTimeImmutable('');
        $this->status = MembershipStatusEnum::PENDING;
        $this->visits = 0;

        return $this;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getStatus(): ?MembershipStatusEnum
    {
        return $this->status;
    }

    public function setStatus(MembershipStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function cancel(MembershipStatusEnum $reason): void
    {
        $this->status = $reason;
    }
}
