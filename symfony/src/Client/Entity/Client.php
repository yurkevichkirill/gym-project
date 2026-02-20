<?php

namespace App\Client\Entity;

use App\Booking\Entity\Booking;
use App\Client\Repository\ClientRepository;
use App\Membership\Entity\Membership;
use App\Payment\Entity\Payment;
use App\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[Gedmo\SoftDeleteable]
class Client extends User
{
    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['public-client', 'create-update-client'])]
    private ?int $age = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['public-client', 'create-update-client'])]
    private ?string $balance = "0.00";

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'client', orphanRemoval: true)]
    private Collection $bookings;

    /**
     * @var Collection<int, Membership>
     */
    #[ORM\OneToMany(targetEntity: Membership::class, mappedBy: 'client', orphanRemoval: true)]
    private Collection $memberships;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'client')]
    private Collection $payments;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->memberships = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->setRoles(['ROLE_CLIENT']);
    }

    public function getRoles(): array
    {
        $roles = parent::getRoles();
        if (!in_array('ROLE_CLIENT', $roles, true)) {
            $roles[] = 'ROLE_CLIENT';
        }
        return array_unique($roles);
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setClient($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getClient() === $this) {
                $booking->setClient(null);
            }
        }

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
            $membership->setClient($this);
        }

        return $this;
    }

    public function removeMembership(Membership $membership): static
    {
        if ($this->memberships->removeElement($membership)) {
            // set the owning side to null (unless already changed)
            if ($membership->getClient() === $this) {
                $membership->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setClient($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getClient() === $this) {
                $payment->setClient(null);
            }
        }

        return $this;
    }
}
