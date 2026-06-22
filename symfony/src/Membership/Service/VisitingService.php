<?php

declare(strict_types=1);

namespace App\Membership\Service;

use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Exception\NoActiveMembershipException;
use App\Membership\Repository\MembershipRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

final readonly class VisitingService
{
    public function __construct(
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

        $activeMembership = $this->membershipRepo->recordVisit($client, new DateTimeImmutable());
        if ($activeMembership === null) {
            $this->membershipLogger->notice('Membership visit rejected: no active membership', $context + [
                    'outcome' => 'rejected',
                ]);
            throw new NoActiveMembershipException();
        }

        return $activeMembership;
    }

    public function checkOnExpire(Membership $membership): void
    {
        $endDate = $membership->getEndDate();
        if (
            $membership->getSessionLimit() !== null && $membership->getVisits() >= $membership->getSessionLimit() ||
            ($endDate !== null && new DateTimeImmutable() > $endDate)
        ) {
            $membership->setStatus(MembershipStatusEnum::EXPIRED);
        }
    }
}
