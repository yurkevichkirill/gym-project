<?php

namespace App\TrainingType\Entity;

use App\Trainer\Entity\Trainer;
use App\TrainingType\Repository\TrainingTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrainingTypeRepository::class)]
class TrainingType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['public-trainer', 'public-training-type'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups('public-training-type')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups('public-training-type')]
    private ?string $description = null;

    /**
     * @var Collection<int, Trainer>
     */
    #[ORM\OneToMany(targetEntity: Trainer::class, mappedBy: 'training_type')]
    private Collection $trainers;

    public function __construct()
    {
        $this->trainers = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Trainer>
     */
    public function getTrainers(): Collection
    {
        return $this->trainers;
    }

    public function addTrainer(Trainer $trainer): static
    {
        if (!$this->trainers->contains($trainer)) {
            $this->trainers->add($trainer);
            $trainer->setTrainingType($this);
        }

        return $this;
    }

    public function removeTrainer(Trainer $trainer): static
    {
        if ($this->trainers->removeElement($trainer)) {
            // set the owning side to null (unless already changed)
            if ($trainer->getTrainingType() === $this) {
                $trainer->setTrainingType(null);
            }
        }

        return $this;
    }
}
