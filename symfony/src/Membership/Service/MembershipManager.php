<?php

declare(strict_types=1);

namespace App\Membership\Service;

use App\Client\Entity\Client;
use App\User\Service\AvailabilityService;
use App\Exception\InvalidMembershipStatusException;
use App\Exception\MembershipActiveException;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Repository\MembershipRepository;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Service\PaymentService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class MembershipManager
{
    public function __construct(
        private MembershipRepository $membershipRepo,
        private MembershipPlanRepository $membershipPlanRepo,
        private EntityManagerInterface $entityManager,
        private AvailabilityService $userAvailabilityService,
        private VisitingService $visitingService,
        private PaymentService $clientPaymentService,
    )
    {}

    public function create(Client $client, int $membershipPlanId): Membership
    {
        $this->userAvailabilityService->ensureNotBlocked($client);

        $plan = $this->membershipPlanRepo->find($membershipPlanId);

        if ($plan === null) {
            throw new NotFoundHttpException('Membership plan not found');
        }

        return $this->entityManager->wrapInTransaction(function () use ($client, $plan) {
            if ($this->visitingService->hasActiveMembership($client)) {
                throw new MembershipActiveException("Client still has active membership");
            }

            $payment = $this->clientPaymentService->createPayment(
                client: $client,
                amount: (int) round((float) $plan->getPrice()),
                category: PaymentCategoryEnum::MEMBERSHIP,
            );

            $membership = new Membership();
            $membership->setClient($client);
            $membership->setPlan($plan);

            $membership->setName($plan->getName());
            $membership->setDurationDays($plan->getDurationDays());
            $membership->setSessionLimit($plan->getSessionLimit());

            $membership->setPayment($payment);

            $this->membershipRepo->create($membership);

            $this->entityManager->flush();

            return $membership;
        });
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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function expire(): int
    {
        $curDate = new DateTimeImmutable();
        $expiredMemberships = $this->membershipRepo->findExpired($curDate);

        foreach ($expiredMemberships as $membership) {
            if ($membership->getStatus() === MembershipStatusEnum::ACTIVE && $membership->getEndDate() <= $curDate) {
                $membership->setStatus(MembershipStatusEnum::EXPIRED);
            }
        }

        $this->entityManager->flush();

        return count($expiredMemberships);
    }
}
