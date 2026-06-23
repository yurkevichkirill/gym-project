<?php

declare(strict_types=1);

namespace App\Membership\Service;

use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\Membership\Repository\MembershipRepository;
use DateTimeImmutable;

final readonly class MembershipAvailabilityService
{
    public function __construct(
        private MembershipRepository $membershipRepo,
    )
    {}

    public function hasActiveMembership(Client $client, ?DateTimeImmutable $date = null): bool
    {
        $activeMembership = $this->membershipRepo->findActive($client);
        if ($activeMembership === null) {
            return false;
        }

        $checkedDate = ($date ?? new DateTimeImmutable())->setTime(0, 0);

        return $this->isAvailableOnDate($activeMembership, $checkedDate);
    }

    private function isAvailableOnDate(Membership $membership, DateTimeImmutable $date): bool
    {
        if (
            $membership->getSessionLimit() !== null
            && $membership->getVisits() >= $membership->getSessionLimit()
        ) {
            return false;
        }

        $startDate = $membership->getStartDate();
        $endDate = $membership->getEndDate();

        if ($startDate === null || $endDate === null) {
            return false;
        }

        $checkedDate = $date->setTime(0, 0);
        $membershipStartDate = $startDate->setTime(0, 0);
        $membershipEndDate = $endDate->setTime(0, 0);

        return $membershipStartDate <= $checkedDate
            && $membershipEndDate >= $checkedDate;
    }
}
