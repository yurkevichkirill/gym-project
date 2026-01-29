<?php

namespace App\Entity;

use App\Enum\DayOfWeekEnum;
use App\Repository\TrainingRepository;
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
    #[Groups(['public-training', 'public-booking'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trainings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('public-training')]
    private ?Trainer $trainer = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups('public-training')]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $start_time = null;

    #[ORM\Column(type: Types::ENUM)]
    #[Groups('public-training')]
    #[Assert\NotBlank]
    private ?DayOfWeekEnum $day_of_week = null;

    #[ORM\Column(options: ['default' => 60, 'check' => "duration_minutes" > 0])]
    #[Groups('public-training')]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Assert\GreaterThanOrEqual(60)]
    private ?int $duration_minutes = null;

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
        return $this->start_time;
    }

    public function setStartTime(\DateTimeImmutable $start_time): static
    {
        $this->start_time = $start_time;

        return $this;
    }

    public function getDayOfWeek(): ?DayOfWeekEnum
    {
        return $this->day_of_week;
    }

    public function setDayOfWeek(DayOfWeekEnum $day_of_week): static
    {
        $this->day_of_week = $day_of_week;

        return $this;
    }

    public function getDurationMinutes(): ?int
    {
        return $this->duration_minutes;
    }

    public function setDurationMinutes(int $duration_minutes): static
    {
        $this->duration_minutes = $duration_minutes;

        return $this;
    }
}
