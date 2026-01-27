<?php

namespace App\Entity;

use App\Enum\DayOfWeekEnum;
use App\Repository\TrainerAvailabilityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrainerAvailabilityRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_trainer_day', columns: ['trainer_id', 'day_of_week'])]
class TrainerAvailability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trainerAvailabilities')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['public-trainer-availability'])]
    private ?Trainer $trainer = null;

    #[ORM\Column(type: Types::ENUM)]
    #[Groups(['public-trainer-availability'])]
    #[Assert\NotBlank]
    private ?DayOfWeekEnum $day_of_week = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['public-trainer-availability'])]
    #[Assert\NotBlank]
    #questions
    private ?\DateTimeImmutable $start_time = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['public-trainer-availability'])]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $end_time = null;

    #[ORM\Column(options: ['default' => 60, 'check' => "duration_minutes" > 0])]
    #[Groups(['public-trainer-availability'])]
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

    public function getDayOfWeek(): ?DayOfWeekEnum
    {
        return $this->day_of_week;
    }

    public function setDayOfWeek(DayOfWeekEnum $day_of_week): static
    {
        $this->day_of_week = $day_of_week;

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

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->end_time;
    }

    public function setEndTime(\DateTimeImmutable $end_time): static
    {
        $this->end_time = $end_time;

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
