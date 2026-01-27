<?php

namespace App\Entity;

use App\Repository\MembershipPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MembershipPlanRepository::class)]
class MembershipPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('public-membership-plan')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups('public-membership-plan')]
    private ?string $name = null;

    #[ORM\Column(options: ['check' => "duration_days" > 0])]
    #[Groups('public-membership-plan')]
    private ?int $duration_days = null;

    #[ORM\Column(nullable: true)]
    #[Groups('public-membership-plan')]
    private ?int $session_limit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups('public-membership-plan')]
    private ?string $price = null;

    /**
     * @var Collection<int, Membership>
     */
    #[ORM\OneToMany(targetEntity: Membership::class, mappedBy: 'plan')]
    private Collection $memberships;

    public function __construct()
    {
        $this->memberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDurationDays(): ?int
    {
        return $this->duration_days;
    }

    public function setDurationDays(int $duration_days): static
    {
        $this->duration_days = $duration_days;

        return $this;
    }

    public function getSessionLimit(): ?int
    {
        return $this->session_limit;
    }

    public function setSessionLimit(?int $session_limit): static
    {
        $this->session_limit = $session_limit;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, Membership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function addMembership(Membership $membership): static
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setPlan($this);
        }

        return $this;
    }

    public function removeMembership(Membership $membership): static
    {
        if ($this->memberships->removeElement($membership)) {
            // set the owning side to null (unless already changed)
            if ($membership->getPlan() === $this) {
                $membership->setPlan(null);
            }
        }

        return $this;
    }
}
