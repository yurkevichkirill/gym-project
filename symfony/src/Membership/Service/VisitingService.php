<?php

declare(strict_types=1);

namespace App\Membership\Service;

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
        public MembershipRepository $membershipRepo,
        private LoggerInterface $membershipLogger,
    )
    {}

    public function visit(Client $client): Membership
    {
        $context = [
            'domain' => 'membership',
            'operation' => 'visit',
            'outcome' => 'started',
            'client_id' => $client->getId(),
        ];

        $this->membershipLogger->info('Membership visit started', $context);

        $activeMembership = $this->membershipRepo->findActive($client);

        if ($activeMembership === null) {
            $this->membershipLogger->notice('Membership visit rejected: no active membership', $context + [
                'outcome' => 'rejected',
            ]);
            throw new NoActiveMembershipException();
        }

        $activeMembership->setVisits($activeMembership->getVisits() + 1);

        $this->checkOnExpire($activeMembership);

        $this->membershipLogger->info('Membership visit recorded', $this->membershipContext($activeMembership, [
            'operation' => 'visit',
            'outcome' => 'succeeded',
        ]));

        return $activeMembership;
    }

    public function checkOnExpire(Membership $membership): void
    {
        if (
            $membership->getSessionLimit() !== null && $membership->getVisits() >= $membership->getSessionLimit() ||
            new DateTimeImmutable() > $membership->getEndDate()
        ) {
            $membership->setStatus(MembershipStatusEnum::EXPIRED);

            $this->membershipLogger->info('Membership expired during visit validation', $this->membershipContext($membership, [
                'operation' => 'expire_on_visit',
                'outcome' => 'succeeded',
            ]));
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

    private function membershipContext(Membership $membership, array $extra = []): array
    {
        return $extra + [
            'domain' => 'membership',
            'membership_id' => $membership->getId(),
            'client_id' => $membership->getClient()?->getId(),
            'membership_plan_id' => $membership->getPlan()?->getId(),
            'membership_plan_name' => $membership->getPlan()?->getName(),
            'payment_id' => $membership->getPayment()?->getId(),
            'payment_method' => $membership->getPayment()?->getMethod()?->value,
            'payment_status' => $membership->getPayment()?->getStatus()?->value,
            'status' => $membership->getStatus()?->value,
            'visits' => $membership->getVisits(),
            'session_limit' => $membership->getSessionLimit(),
            'duration_days' => $membership->getDurationDays(),
            'start_date' => $membership->getStartDate()?->format(DATE_ATOM),
            'end_date' => $membership->getEndDate()?->format(DATE_ATOM),
            'frozen_at' => $membership->getFrozenAt()?->format(DATE_ATOM),
        ];
    }
}
