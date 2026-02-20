<?php

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
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrainerWorkTimeRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_trainer_day', columns: ['trainer_id', 'date'])]
class TrainerWorkTime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['public-trainer-worktime'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trainerWorkTime')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['public-trainer-worktime', 'public-trainer-free-slots'])]
    private ?Trainer $trainer = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['public-trainer-worktime', 'public-trainer-free-slots', 'create-update-trainer-worktime', 'public-training'])]
    #[Assert\NotBlank]
    #[Context([DateTimeNormalizer::FORMAT_KEY => "Y-m-d"])]
    private ?DateTimeImmutable $date = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['public-trainer-worktime', 'create-update-trainer-worktime'])]
    #[Assert\NotBlank]
    #[Context([DateTimeNormalizer::FORMAT_KEY => "H:i"])]
    private ?DateTimeImmutable $startTime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['public-trainer-worktime', 'create-update-trainer-worktime'])]
    #[Assert\NotBlank]
    #[Context([DateTimeNormalizer::FORMAT_KEY => "H:i"])]
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
    #[Groups(['public-trainer-worktime', 'public-trainer-free-slots'])]
    #[SerializedName('freeSlots')]
    #[Context([DateTimeNormalizer::FORMAT_KEY => "H:i"])]
    public function getFreeSlots(): array
    {
        $trainingsArray = $this->trainings->getValues();
        $startTrainerTime = $this->startTime;
        $endTrainerTime = $this->endTime;
        usort($trainingsArray, fn ($training1, $training2) => $training1->getStartTime()->format("H:i:s") <=> $training2->getStartTime()->format("H:i:s"));

        $available = [];
        $startPeriod = $startTrainerTime;
        foreach ($trainingsArray as $dayTraining) {
            $available[] = [
                "start" => $startPeriod->format("H:i:s"),
                "end" => $dayTraining->getStartTime()->format("H:i:s"),
            ];
            $startPeriod = $dayTraining->getStartTime()->add(new DateInterval("PT" . $dayTraining->getDurationMinutes() . "M"));
        }
        if(isset($available[0]) && $available[0]['start'] === $available[0]['end']) {
            array_shift($available);
        }
        if ($startPeriod->format('H:i:s') !== $endTrainerTime->format("H:i:s")) {
            $available[] = [
                "start" => $startPeriod->format("H:i:s"),
                "end" => $endTrainerTime->format("H:i:s"),
            ];
        }

        return $available;
    }
}
