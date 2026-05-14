<?php

declare(strict_types=1);

namespace App\Membership\Service;

use App\Booking\Service\BookingAvailabilityService;
use App\Client\Entity\Client;
use App\Exception\NoActiveMembershipException;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Repository\MembershipRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

final readonly class VisitingService
{
    public function __construct(
        private BookingAvailabilityService $bookingAvailabilityService,
        public MembershipRepository $membershipRepo,
        private LoggerInterface $membershipLogger,
    )
    {}

    /**
     * @throws NoActiveMembershipException
     */
    public function visit(Client $client): Membership
    {
        $context = [
            'domain' => 'membership',
            'operation' => 'visit',
            'client_id' => $client->getId(),
        ];

        if (!$this->bookingAvailabilityService->hasActiveMembership($client)) {
            $this->membershipLogger->notice('Membership visit rejected: no active membership', $context + [
                    'outcome' => 'rejected',
                ]);
            throw new NoActiveMembershipException();
        }

        $activeMembership = $this->membershipRepo->findActive($client);

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
}
