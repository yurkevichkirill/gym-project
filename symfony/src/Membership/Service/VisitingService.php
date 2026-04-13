<?php

declare(strict_types=1);

namespace App\Membership\Service;

use App\Client\Entity\Client;
use App\Exception\NoActiveMembershipException;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Repository\MembershipRepository;
use DateTimeImmutable;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class VisitingService
{
    public function __construct(
        public MembershipRepository $membershipRepo,
    )
    {}

    public function visit(Client $client): Membership
    {
        $activeMembership = $this->membershipRepo->findActive($client);

        if ($activeMembership === null) {
            throw new NoActiveMembershipException();
        }

        $activeMembership->setVisits($activeMembership->getVisits() + 1);

        $this->checkOnExpire($activeMembership);

        return $activeMembership;
    }

    public function checkOnExpire(Membership $membership): void
    {
        if (
            $membership->getSessionLimit() !== null && $membership->getVisits() >= $membership->getSessionLimit() ||
            new DateTimeImmutable() > $membership->getEndDate()
        ) {
            $membership->setStatus(MembershipStatusEnum::EXPIRED);
        }
    }

    public function hasActiveMembership(Client $client, ?DateTimeImmutable $date = null): bool
    {
        $activeMembership = $this->membershipRepo->findActive($client);

        if (
            $activeMembership === null ||
            $activeMembership->getSessionLimit() !== null && $activeMembership->getVisits() >= $activeMembership->getSessionLimit() ||
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
