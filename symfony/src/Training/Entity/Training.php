<?php

namespace App\Training\Entity;

use App\Enum\DayOfWeekEnum;
use App\Trainer\Entity\Trainer;
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
    #[Groups(['public-training', 'public-booking', 'create-booking'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trainings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['public-training'])]
    private ?Trainer $trainer = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['public-training', 'create-update-training'])]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(type: Types::ENUM)]
    #[Groups(['public-training', 'create-update-training'])]
    #[Assert\NotBlank]
    private ?DayOfWeekEnum $dayOfWeek = null;

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

    public function getTrainer(): ?Trainer
    {
        return $this->trainer;
    }

    public function setTrainer(?Trainer $trainer): static
    {
        $this->trainer = $trainer;

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

    public function getDayOfWeek(): ?DayOfWeekEnum
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(DayOfWeekEnum $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

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
