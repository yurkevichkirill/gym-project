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
    private ?string $pricePerHour = null;

    #[ORM\Column]
    private ?string $photoUrl = null;

    #[ORM\Column]
    private ?string $education = null;

    #[ORM\Column]
    private ?string $about = null;

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

    public function getEducation(): ?string
    {
        return $this->education;
    }

    public function setEducation(?string $education): void
    {
        $this->education = $education;
    }

    public function getAbout(): ?string
    {
        return $this->about;
    }

    public function setAbout(?string $about): void
    {
        $this->about = $about;
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

    public function getPricePerHour(): ?string
    {
        return $this->pricePerHour;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): void
    {
        $this->photoUrl = $photoUrl;
    }

    public function setPricePerHour(string $pricePerHour): static
    {
        $this->pricePerHour = $pricePerHour;

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
