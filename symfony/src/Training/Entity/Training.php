<?php

declare(strict_types=1);

namespace App\Training\Entity;

use App\Booking\Entity\Booking;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Exception\TrainingCrossesMidnightException;
use App\Training\Repository\TrainingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingRepository::class)]
final class Training
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trainings')]
    #[ORM\JoinColumn(nullable: false)]
    private TrainerWorkTime $trainerWorkTime;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(options: ['default' => 60])]
    private int $durationMinutes = 60;

    #[ORM\OneToOne(targetEntity: Booking::class, mappedBy: 'training', cascade: ['persist'])]
    private ?Booking $booking = null;

    #[ORM\Column]
    private bool $isBusy = true;

    public function isBusy(): bool
    {
        return $this->isBusy;
    }

    public function setIsBusy(bool $isBusy): static
    {
        $this->isBusy = $isBusy;

        return $this;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        if ($this->booking !== null && $this->booking !== $booking) {
            $this->booking->setTraining(null);
        }

        $this->booking = $booking;

        if ($booking !== null && $this !== $booking->getTraining()) {
            $booking->setTraining($this);
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrainerWorkTime(): TrainerWorkTime
    {
        return $this->trainerWorkTime;
    }

    public function setTrainerWorkTime(TrainerWorkTime $trainerWorkTime): static
    {
        $this->trainerWorkTime = $trainerWorkTime;

        return $this;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): static
    {
        $this->assertFitsWithinCalendarDay($startTime, $this->durationMinutes);
        $this->startTime = $startTime;

        return $this;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $durationMinutes): static
    {
        if (isset($this->startTime)) {
            $this->assertFitsWithinCalendarDay($this->startTime, $durationMinutes);
        }

        $this->durationMinutes = $durationMinutes;

        return $this;
    }

    private function assertFitsWithinCalendarDay(
        \DateTimeImmutable $startTime,
        int $durationMinutes,
    ): void {
        $startSeconds = ((int) $startTime->format('H') * 3600)
            + ((int) $startTime->format('i') * 60)
            + (int) $startTime->format('s');

        if ($startSeconds + ($durationMinutes * 60) >= 86400) {
            throw new TrainingCrossesMidnightException();
        }
    }
}
