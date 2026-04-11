<?php

declare(strict_types=1);

namespace App\Membership\Service;

use App\Client\Entity\Client;
use App\Client\Service\AvailabilityService;
use App\Exception\InvalidMembershipStatusException;
use App\Exception\MembershipActiveException;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Repository\MembershipRepository;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\Payment\Service\PaymentService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class MembershipManager
{
    public function __construct(
        private MembershipRepository $membershipRepo,
        private MembershipPlanRepository $membershipPlanRepo,
        private EntityManagerInterface $entityManager,
        private AvailabilityService $clientAvailabilityService,
        private PaymentService $clientPaymentService,
    )
    {}

    public function create(Client $client, int $membershipPlanId): Membership
    {
        $this->clientAvailabilityService->ensureNotBlocked($client);

        $plan = $this->membershipPlanRepo->find($membershipPlanId);

        if ($plan === null) {
            throw new NotFoundHttpException('Membership plan not found');
        }

        return $this->entityManager->wrapInTransaction(function () use ($client, $plan) {
            if ($this->hasActiveMembership($client)) {
                throw new MembershipActiveException("Client still has active membership");
            }

            $payment = $this->clientPaymentService->pay($client, (float) $plan->getPrice());

            $membership = new Membership();
            $membership->setClient($client);
            $membership->setPlan($plan);

            $membership->setName($plan->getName());
            $membership->setDurationDays($plan->getDurationDays());
            $membership->setSessionLimit($plan->getSessionLimit());

            $membership->setPayment($payment);

            $this->membershipRepo->create($membership);

            return $membership;
        });
    }

    public function hasActiveMembership(Client $client): bool
    {
        $activeMembership = $this->membershipRepo->findOneBy([
            'client' => $client,
            'status' => MembershipStatusEnum::ACTIVE
        ]);

        if (
            !$activeMembership ||
            $activeMembership->getDurationDays() !== null && $activeMembership->getVisits() >= $activeMembership->getSessionLimit() ||
            new DateTimeImmutable() > $activeMembership->getEndDate()
        ) {
            return false;
        }

        return true;
    }

    public function freeze(Membership $membership): Membership
    {
        if ($membership->getStatus() != MembershipStatusEnum::ACTIVE) {
            throw new InvalidMembershipStatusException();
        }

        $membership->setFrozenAt(new DateTimeImmutable());
        $membership->setStatus(MembershipStatusEnum::FROZEN);

        $this->entityManager->flush();

        return $membership;
    }

    public function unfreeze(Membership $membership): Membership
    {
        if ($membership->getStatus() != MembershipStatusEnum::FROZEN) {
            throw new InvalidMembershipStatusException("Only frozen membership can be unfrozen");
        }

        $dateInterval = $membership->getFrozenAt()->diff(new DateTimeImmutable());
        $membership->setEndDate($membership->getEndDate()->add($dateInterval));
        $membership->setFrozenAt(null);
        $membership->setStatus(MembershipStatusEnum::ACTIVE);

        $this->entityManager->flush();

        return $membership;
    }

    public function renew(Membership $membership): Membership
    {
        if ($membership->getPlan() === null) {
            throw new NotFoundHttpException("This membership type no longer exists");
        }

        return $this->create($membership->getClient(), $membership->getPlan()->getId());
    }

    public function terminate(Membership $membership): Membership
    {
        if ($membership->getStatus() === MembershipStatusEnum::EXPIRED) {
            throw new InvalidMembershipStatusException("Membership already expired");
        }

        $membership->setEndDate(new DateTimeImmutable());
        $membership->setStatus(MembershipStatusEnum::EXPIRED);

        $this->entityManager->flush();

        return $membership;
    }
}
