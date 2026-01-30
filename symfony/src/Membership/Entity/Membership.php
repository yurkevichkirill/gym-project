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
    #[Groups('public-membership')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups('public-membership')]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups('public-membership')]
    private ?MembershipPlan $plan = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups('public-membership')]
    private ?\DateTimeImmutable $start_date = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups('public-membership')]
    private ?\DateTimeImmutable $end_date = null;

    #[ORM\Column(type: Types::ENUM, options: ['default' => MembershipStatusEnum::ACTIVE])]
    #[Groups('public-membership')]
    private ?MembershipStatusEnum $status = null;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups('public-membership')]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $visits = null;

    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeImmutable $created_at = null;

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

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->start_date;
    }

    public function setStartDate($start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    /**
     * @throws DateMalformedIntervalStringException
     */
    #[ORM\PrePersist]
    public function initializeDefaults(): static
    {
        $this->created_at = new DateTimeImmutable('');
        $this->start_date = $this->created_at->add(new DateInterval('P1D'));
        $this->end_date = $this->start_date->add(new DateInterval("P" . $this->plan->getDurationDays() . "D"));
        $this->status = MembershipStatusEnum::ACTIVE;
        $this->visits = 0;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->end_date;
    }

    public function setEndDate($end_date): static
    {
        $this->end_date = $end_date;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
