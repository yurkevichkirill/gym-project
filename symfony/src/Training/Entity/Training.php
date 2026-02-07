<?php

namespace App\Training\Entity;

use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Repository\TrainingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrainingRepository::class)]
class Training
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['public-training', 'public-booking', 'create-update-booking'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trainings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['public-training'])]
    private ?TrainerWorkTime $trainerWorkTime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['public-training', 'create-update-training'])]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(options: ['default' => 60, 'check' => "duration_minutes" > 0])]
    #[Groups(['public-training', 'create-update-training'])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Assert\GreaterThanOrEqual(60)]
    private ?int $durationMinutes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrainerWorkTime(): TrainerWorkTime
    {
        return $this->trainerWorkTime;
    }

    public function setTrainerWorkTime(?TrainerWorkTime $trainerWorkTime): static
    {
        $this->trainerWorkTime = $trainerWorkTime;

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getDurationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $durationMinutes): static
    {
        $this->durationMinutes = $durationMinutes;

        return $this;
    }
}
