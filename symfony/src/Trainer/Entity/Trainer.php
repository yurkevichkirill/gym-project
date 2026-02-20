<?php

namespace App\Trainer\Entity;

use App\Payment\Entity\Payment;
use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainingType\Entity\TrainingType;
use App\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainerRepository::class)]
class Trainer extends User
{
    #[ORM\ManyToOne(targetEntity: TrainingType::class, inversedBy: 'trainers')]
    #[ORM\JoinColumn(name: 'training_type_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?TrainingType $trainingType = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    /**
     * @var Collection<int, TrainerWorkTime>
     */
    #[ORM\OneToMany(targetEntity: TrainerWorkTime::class, mappedBy: 'trainer', orphanRemoval: true)]
    private Collection $trainerWorkTime;

    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'trainer')]
    private Collection $payments;


    public function __construct()
    {
        $this->trainerWorkTime = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->setRoles(['ROLE_TRAINER']);
    }

    public function getTrainingType(): ?TrainingType
    {
        return $this->trainingType;
    }

    public function setTrainingType(?TrainingType $trainingType): static
    {
        $this->trainingType = $trainingType;

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
     * @return Collection<int, TrainerWorkTime>
     */
    public function getTrainerWorkTime(): Collection
    {
        return $this->trainerWorkTime;
    }

    public function addTrainerWorkTime(TrainerWorkTime $trainerWorkTime): static
    {
        if (!$this->trainerWorkTime->contains($trainerWorkTime)) {
            $this->trainerWorkTime->add($trainerWorkTime);
            $trainerWorkTime->setTrainer($this);
        }

        return $this;
    }

    public function removeTrainerWorkTime(TrainerWorkTime $trainerWorkTime): static
    {
        if ($this->trainerWorkTime->removeElement($trainerWorkTime)) {
            if ($trainerWorkTime->getTrainer() === $this) {
                $trainerWorkTime->setTrainer(null);
            }
        }

        return $this;
    }

    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setTrainer($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getTrainer() === $this) {
                $payment->setTrainer(null);
            }
        }

        return $this;
    }
}
