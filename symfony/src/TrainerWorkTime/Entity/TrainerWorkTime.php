<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Entity;

use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Trainer\Entity\Trainer;
use App\Training\Entity\Training;
use DateInterval;
use DateMalformedIntervalStringException;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainerWorkTimeRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_trainer_day', columns: ['trainer_id', 'date'])]
final class TrainerWorkTime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trainerWorkTime')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trainer $trainer = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $date = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?DateTimeImmutable $startTime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?DateTimeImmutable $endTime = null;

    #[ORM\OneToMany(targetEntity: Training::class, mappedBy: 'trainerWorkTime', cascade: ['persist', 'remove'])]
    private Collection $trainings;

    public function __construct()
    {
        $this->trainings = new ArrayCollection();
    }

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

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getStartTime(): ?DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;

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
            $training->setTrainerWorkTime($this);
        }

        return $this;
    }

    public function removeTraining(Training $training): static
    {
        if ($this->trainings->removeElement($training)) {
            // set the owning side to null (unless already changed)
            if ($training->getTrainerWorkTime() === $this) {
                $training->setTrainerWorkTime(null);
            }
        }

        return $this;
    }

    /**
     * @throws DateMalformedIntervalStringException
     */
    public function getFreeSlots(): array
    {
        $busyTrainings = array_filter(
            $this->trainings->getValues(),
            fn ($training) => $training->isBusy()
        );

        usort($busyTrainings, fn ($a, $b) =>
            $a->getStartTime() <=> $b->getStartTime()
        );

        $available = [];

        $current = $this->startTime;

        foreach ($busyTrainings as $training) {
            $trainingStart = $training->getStartTime();

            if ($current < $trainingStart) {
                $available[] = [
                    'start' => $current->format('H:i:s'),
                    'end' => $trainingStart->format('H:i:s'),
                ];
            }

            $current = $trainingStart->add(
                new DateInterval("PT{$training->getDurationMinutes()}M")
            );
        }

        if ($current < $this->endTime) {
            $available[] = [
                'start' => $current->format('H:i:s'),
                'end' => $this->endTime->format('H:i:s'),
            ];
        }

        return $available;
    }
}
