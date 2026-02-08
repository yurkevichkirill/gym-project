<?php

namespace App\Trainer\Entity;

use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainingType\Entity\TrainingType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrainerRepository::class)]
class Trainer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['public-trainer', 'public-trainer-worktime', 'public-trainer-free-slots', 'public-training'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['public-trainer', 'create-update-trainer'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['public-trainer', 'create-update-trainer'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\Email]
    #[Groups(['public-trainer', 'create-update-trainer'])]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Groups(['public-trainer', 'create-update-trainer'])]
    private ?string $phone = null;

    #[ORM\ManyToOne(targetEntity: TrainingType::class, inversedBy: 'trainers')]
    #[ORM\JoinColumn(name: 'training_type_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['public-trainer', 'create-update-trainer'])]
    private ?TrainingType $trainingType = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['public-trainer', 'create-update-trainer'])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $price = null;

    /**
     * @var Collection<int, TrainerWorkTime>
     */
    #[ORM\OneToMany(targetEntity: TrainerWorkTime::class, mappedBy: 'trainer')]
    private Collection $trainerWorkTime;


    public function __construct()
    {
        $this->trainerWorkTime = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
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

    public function addTrainerAvailability(TrainerWorkTime $trainerAvailability): static
    {
        if (!$this->trainerWorkTime->contains($trainerAvailability)) {
            $this->trainerWorkTime->add($trainerAvailability);
            $trainerAvailability->setTrainer($this);
        }

        return $this;
    }

    public function removeTrainerAvailability(TrainerWorkTime $trainerAvailability): static
    {
        if ($this->trainerWorkTime->removeElement($trainerAvailability)) {
            // set the owning side to null (unless already changed)
            if ($trainerAvailability->getTrainer() === $this) {
                $trainerAvailability->setTrainer(null);
            }
        }

        return $this;
    }
}
