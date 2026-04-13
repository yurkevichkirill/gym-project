<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Service;

use App\TrainerWorkTime\Entity\TrainerWorkTime;
use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class AvailabilityService
{
    /**
     * @throws DateMalformedIntervalStringException
     * @throws DateMalformedStringException
     */
    public function isTimeAvailable(TrainerWorkTime $worktime, string $startTime, int $durationMinutes, ?string $oldStartTime = null, ?int $oldDurationMinutes = null): bool
    {
        $endTime = new DateTimeImmutable($startTime)
            ->add(new DateInterval('PT' . $durationMinutes . 'M'))
            ->format('H:i:s');
        $freeSlots = $worktime->getFreeSlots();
        if ($oldDurationMinutes && $oldStartTime) {
            $freeSlots = $this->getFreeSlotsExcept($freeSlots, $oldStartTime, $oldDurationMinutes);
        }

        return array_any($freeSlots, fn($slot) => $startTime >= $slot['start'] && $endTime <= $slot['end']);
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    private function getFreeSlotsExcept(array $freeSlots, string $oldStartTime, int $oldDurationMinutes): array
    {
        $excludeSlot = [
            'start' => $oldStartTime,
            'end' => new DateTimeImmutable($oldStartTime)
                ->add(new DateInterval('PT' . $oldDurationMinutes . 'M'))
                ->format('H:i:s')
        ];

        $allSlots = array_merge($freeSlots, [$excludeSlot]);
        usort($allSlots, fn($s1, $s2) => $s1['start'] <=> $s2['start']);
        return $this->mergeOverlappingSlots($allSlots);
    }

    private function mergeOverlappingSlots(array $slots): array
    {
        if (empty($slots)) return [];

        $merged = [$slots[0]];

        foreach ($slots as $slot) {
            $last = &$merged[count($merged) - 1];

            if ($slot['start'] <= $last['end']) {
                $last['end'] = max($last['end'], $slot['end']);
            } else {
                $merged[] = $slot;
            }
        }

        return $merged;
    }
}
