<?php

namespace App\TrainerWorkTime\Entity;

use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Enum\DayOfWeekEnum;
use App\Trainer\Entity\Trainer;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrainerWorkTimeRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_trainer_day', columns: ['trainer_id', 'day_of_week'])]
class TrainerWorkTime
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
    #[Groups(['public-trainer-availability', 'create-update-trainer-availability'])]
    #[Assert\NotBlank]
    private ?DayOfWeekEnum $dayOfWeek = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['public-trainer-availability', 'create-update-trainer-availability'])]
    #[Assert\NotBlank]
    #questions
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['public-trainer-availability', 'create-update-trainer-availability'])]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $endTime = null;

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
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(DayOfWeekEnum $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

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

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }
}
