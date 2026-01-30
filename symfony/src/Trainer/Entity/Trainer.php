<?php

namespace App\Trainer\Entity;

use App\TrainerAvailability\Entity\TrainerAvailability;
use App\Trainer\Repository\TrainerRepository;
use App\Training\Entity\Training;
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
    #[Groups(['public-trainer', 'public-trainer-availability', 'public-training'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['public-trainer'])]
    private ?string $first_name = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['public-trainer'])]
    private ?string $last_name = null;

    #[ORM\Column(length: 255)]
    #[Assert\Email]
    #[Groups(['public-trainer'])]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Groups(['public-trainer'])]
    private ?string $phone = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'trainers')]
    #[ORM\JoinColumn(name: 'training_type_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['public-trainer'])]
    private ?TrainingType $training_type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['public-trainer'])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $price = null;

    /**
     * @var Collection<int, TrainerAvailability>
     */
    #[ORM\OneToMany(targetEntity: TrainerAvailability::class, mappedBy: 'trainer')]
    private Collection $trainerAvailabilities;

    /**
     * @var Collection<int, Training>
     */
    #[ORM\OneToMany(targetEntity: Training::class, mappedBy: 'trainer')]
    private Collection $trainings;

    public function __construct()
    {
        $this->trainerAvailabilities = new ArrayCollection();
        $this->trainings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

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
        return $this->training_type;
    }

    public function setTrainingType(?TrainingType $training_type): static
    {
        $this->training_type = $training_type;

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
     * @return Collection<int, TrainerAvailability>
     */
    public function getTrainerAvailabilities(): Collection
    {
        return $this->trainerAvailabilities;
    }

    public function addTrainerAvailability(TrainerAvailability $trainerAvailability): static
    {
        if (!$this->trainerAvailabilities->contains($trainerAvailability)) {
            $this->trainerAvailabilities->add($trainerAvailability);
            $trainerAvailability->setTrainer($this);
        }

        return $this;
    }

    public function removeTrainerAvailability(TrainerAvailability $trainerAvailability): static
    {
        if ($this->trainerAvailabilities->removeElement($trainerAvailability)) {
            // set the owning side to null (unless already changed)
            if ($trainerAvailability->getTrainer() === $this) {
                $trainerAvailability->setTrainer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Training>
     */
    public function getTrainings(): Collection
    {
        return $this->trainings;
    }

    public function addTraining(Training $training): static
    {
        if (!$this->trainings->contains($training)) {
            $this->trainings->add($training);
            $training->setTrainer($this);
        }

        return $this;
    }

    public function removeTraining(Training $training): static
    {
        if ($this->trainings->removeElement($training)) {
            // set the owning side to null (unless already changed)
            if ($training->getTrainer() === $this) {
                $training->setTrainer(null);
            }
        }

        return $this;
    }
}
