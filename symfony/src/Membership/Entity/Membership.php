<?php

namespace App\Membership\Entity;

use App\Client\Entity\Client;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Repository\MembershipRepository;
use App\MembershipPlan\Entity\MembershipPlan;
use DateInterval;
use DateMalformedIntervalStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
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

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::ENUM, options: ['default' => MembershipStatusEnum::ACTIVE])]
    private ?MembershipStatusEnum $status = null;

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

    public function getPlan(): ?MembershipPlan
    {
        return $this->plan;
    }

    public function setPlan(?MembershipPlan $plan): static
    {
        $this->plan = $plan;

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

    /**
     * @throws DateMalformedIntervalStringException
     */
    #[ORM\PrePersist]
    public function initializeDefaults(): static
    {
        $this->createdAt = new DateTimeImmutable('');
        $this->startDate = $this->createdAt->add(new DateInterval('P1D'));
        $this->endDate = $this->startDate->add(new DateInterval("P" . $this->plan->getDurationDays() . "D"));
        $this->status = MembershipStatusEnum::ACTIVE;
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
}
