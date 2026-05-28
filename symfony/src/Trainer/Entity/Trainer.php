<?php

declare(strict_types=1);

namespace App\Trainer\Entity;

use App\Payment\Entity\Payment;
use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainingType\Entity\TrainingType;
use App\User\Entity\User;
use App\User\Enum\UserRolesEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: TrainerRepository::class)]
#[Gedmo\SoftDeleteable]
final class Trainer extends User
{
    #[ORM\ManyToOne(targetEntity: TrainingType::class, inversedBy: 'trainers')]
    #[ORM\JoinColumn(name: 'training_type_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?TrainingType $trainingType = null;

    #[ORM\Column]
    private int $pricePerHour;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoPath = null;

    #[ORM\Column(nullable: true)]
    private ?string $education = null;

    #[ORM\Column(nullable: true)]
    private ?string $about = null;

    #[ORM\Column]
    private int $balance = 0;

    /** @var Collection<int, TrainerWorkTime> */
    #[ORM\OneToMany(targetEntity: TrainerWorkTime::class, mappedBy: 'trainer')]
    private Collection $trainerWorkTime;

    /** @var Collection<int, Payment> */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'trainer')]
    private Collection $payments;

    public function __construct()
    {
        parent::__construct();
        $this->trainerWorkTime = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->setRoles([UserRolesEnum::ROLE_TRAINER->value]);
    }

    public function getRoles(): array
    {
        $roles = parent::getRoles();

        $trainerRole = UserRolesEnum::ROLE_TRAINER->value;

        if (!in_array($trainerRole, $roles, true)) {
            $roles[] = $trainerRole;
        }

        return array_unique($roles);
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

    public function getPricePerHour(): int
    {
        return $this->pricePerHour;
    }

    public function getPhotoPath(): ?string
    {
        return $this->photoPath;
    }

    public function setPhotoPath(?string $photoPath): static
    {
        $this->photoPath = $photoPath;

        return $this;
    }

    public function setPricePerHour(int $pricePerHour): static
    {
        $this->pricePerHour = $pricePerHour;

        return $this;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function setBalance(int $balance): static
    {
        $this->balance = $balance;

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

    /**
     * @return Collection<int, Payment>
     */
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
