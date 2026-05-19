<?php

declare(strict_types=1);

namespace App\Membership\Service;

use App\Client\Entity\Client;
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

        if (
            $activeMembership === null ||
            ($activeMembership->getSessionLimit() !== null && $activeMembership->getVisits() >= $activeMembership->getSessionLimit()) ||
            new DateTimeImmutable() > $activeMembership->getEndDate()
        ) {
            return false;
        }

        if ($date !== null) {
            if ($activeMembership->getStartDate() > $date || $activeMembership->getEndDate() < $date) {
                return false;
            }
        }

        return true;
    }
}
