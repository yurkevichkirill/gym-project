<?php

declare(strict_types=1);

namespace App\Membership\Service;

use App\Client\Entity\Client;
use App\Infrastructure\ClickHouse\Publisher\AnalyticsPublisher;
use App\Payment\Enum\PaymentMethodEnum;
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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Throwable;

final readonly class MembershipManager
{
    public function __construct(
        private MembershipRepository $membershipRepo,
        private MembershipPlanRepository $membershipPlanRepo,
        private EntityManagerInterface $entityManager,
        private AvailabilityService $userAvailabilityService,
        private VisitingService $visitingService,
        private PaymentService $paymentService,
        private LoggerInterface $membershipLogger,
        private AnalyticsPublisher $analyticsPublisher,
    )
    {}

    public function create(Client $client, int $membershipPlanId): Membership
    {
        $context = $this->membershipActionContext(
            client: $client,
            extra: [
                'membership_plan_id' => $membershipPlanId,
            ],
        );

        $this->membershipLogger->info('Membership creation started', $this->membershipEventContext($context, 'create', 'started'));

        try {
            $this->userAvailabilityService->ensureNotBlocked($client);
        } catch (Throwable $e) {
            $this->membershipLogger->notice('Membership creation rejected: client is blocked', $this->membershipEventContext($context, 'create', 'rejected', [
                'exception_class' => $e::class,
                'exception' => $e,
            ]));

            throw $e;
        }

        $plan = $this->membershipPlanRepo->find($membershipPlanId);

        if ($plan === null) {
            $this->membershipLogger->warning('Membership creation rejected: plan not found', $this->membershipEventContext($context, 'create', 'rejected'));
            throw new NotFoundHttpException('Membership plan not found');
        }

        $context += [
            'membership_plan_id' => $plan->getId(),
            'membership_plan_name' => $plan->getName(),
            'duration_days' => $plan->getDurationDays(),
            'session_limit' => $plan->getSessionLimit(),
            'price' => $plan->getPrice(),
        ];

        try {
            return $this->entityManager->wrapInTransaction(function () use ($client, $plan, $context) {
                if ($this->visitingService->hasActiveMembership($client)) {
                    $this->membershipLogger->notice('Membership creation rejected: active membership already exists', $this->membershipEventContext($context, 'create', 'rejected'));
                    throw new MembershipActiveException('Client still has active membership');
                }

                $membership = new Membership();
                $membership->setClient($client);
                $membership->setPlan($plan);

                $membership->setName($plan->getName());
                $membership->setDurationDays($plan->getDurationDays());
                $membership->setSessionLimit($plan->getSessionLimit());

                $this->membershipRepo->create($membership);

                $membershipContext = $this->membershipContext($membership, [
                    'price' => $plan->getPrice(),
                ]);

                $this->membershipLogger->info('Membership entity created', $this->membershipEventContext($membershipContext, 'create', 'persisted'));

                $price = $plan->getPrice();

                if ($client->getBalance() >= $price) {
                    $this->membershipLogger->info('Membership payment route selected: balance', $this->membershipEventContext($membershipContext, 'payment_route', 'selected', [
                        'payment_method' => PaymentMethodEnum::BALANCE->value,
                        'client_balance' => $client->getBalance(),
                    ]));

                    $payment = $this->paymentService->createPayment(
                        $client,
                        $price,
                        PaymentCategoryEnum::MEMBERSHIP,
                        PaymentMethodEnum::BALANCE,
                    );

                    $this->paymentService->confirmPayment(
                        payment: $payment,
                        membership: $membership,
                    );
                } else {
                    $remaining = $price - $client->getBalance();

                    $this->membershipLogger->info('Membership payment route selected: card', $this->membershipEventContext($membershipContext, 'payment_route', 'selected', [
                        'payment_method' => PaymentMethodEnum::CARD->value,
                        'client_balance' => $client->getBalance(),
                        'remaining_amount' => $remaining,
                    ]));

                    $payment = $this->paymentService->createPayment(
                        $client,
                        $remaining,
                        PaymentCategoryEnum::MEMBERSHIP,
                        PaymentMethodEnum::CARD,
                    );
                }

                $membership->setPayment($payment);

                $this->membershipLogger->info('Membership created successfully', $this->membershipEventContext($this->membershipContext($membership, [
                    'price' => $price,
                ]), 'create', 'succeeded'));

                $this->analyticsPublisher->publish(
                    'membership.created',
                    [
                        'client_id' => $client->getId(),
                        'membership_id' => $membership->getId(),
                        'plan_id' => $membership->getPlan()->getId(),
                        'price' => $membership->getPayment()->getAmount(),
                        'payment_method' => $membership->getPayment()?->getMethod()->value ?? 'unknown',
                    ]
                );

                return $membership;
            });
        } catch (Throwable $e) {
            $this->membershipLogger->error('Membership creation failed', $this->membershipEventContext($context, 'create', 'failed', [
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]));

            throw $e;
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function freeze(Membership $membership): Membership
    {
        $context = $this->membershipContext($membership);

        $this->membershipLogger->info('Membership freeze started', $this->membershipEventContext($context, 'freeze', 'started'));

        if ($membership->getStatus() != MembershipStatusEnum::ACTIVE) {
            $this->membershipLogger->notice('Membership freeze skipped: membership is not active', $this->membershipEventContext($context, 'freeze', 'skipped'));
            throw new InvalidMembershipStatusException();
        }

        $membership->setFrozenAt(new DateTimeImmutable());
        $membership->setStatus(MembershipStatusEnum::FROZEN);

        $this->entityManager->flush();

        $this->membershipLogger->info('Membership frozen', $this->membershipEventContext($this->membershipContext($membership), 'freeze', 'succeeded'));

        $this->analyticsPublisher->publish(
            'membership.froze',
            [
                'client_id' => $membership->getClient()->getId(),
                'membership_id' => $membership->getId(),
                'plan_id' => $membership->getPlan()->getId(),
                'price' => $membership->getPayment()->getAmount(),
                'payment_method' => $membership->getPayment()?->getMethod()->value ?? 'unknown',
            ]
        );

        return $membership;
    }

    /**
     * @throws ExceptionInterface
     */
    public function unfreeze(Membership $membership): Membership
    {
        $context = $this->membershipContext($membership);

        $this->membershipLogger->info('Membership unfreeze started', $this->membershipEventContext($context, 'unfreeze', 'started'));

        if ($membership->getStatus() != MembershipStatusEnum::FROZEN) {
            $this->membershipLogger->notice('Membership unfreeze skipped: membership is not frozen', $this->membershipEventContext($context, 'unfreeze', 'skipped'));
            throw new InvalidMembershipStatusException("Only frozen membership can be unfrozen");
        }

        $dateInterval = $membership->getFrozenAt()->diff(new DateTimeImmutable());
        $membership->setEndDate($membership->getEndDate()->add($dateInterval));
        $membership->setFrozenAt(null);
        $membership->setStatus(MembershipStatusEnum::ACTIVE);

        $this->entityManager->flush();

        $this->membershipLogger->info('Membership unfrozen', $this->membershipEventContext($this->membershipContext($membership), 'unfreeze', 'succeeded'));

        $this->analyticsPublisher->publish(
            'membership.unfroze',
            [
                'client_id' => $membership->getClient()->getId(),
                'membership_id' => $membership->getId(),
                'plan_id' => $membership->getPlan()->getId(),
                'price' => $membership->getPayment()->getAmount(),
                'payment_method' => $membership->getPayment()?->getMethod()->value ?? 'unknown',
            ]
        );

        return $membership;
    }

    /**
     * @throws Throwable
     */
    public function renew(Membership $membership): Membership
    {
        $context = $this->membershipContext($membership);

        $this->membershipLogger->info('Membership renew started', $this->membershipEventContext($context, 'renew', 'started'));

        if ($membership->getPlan() === null) {
            $this->membershipLogger->warning('Membership renew failed: plan not found', $this->membershipEventContext($context, 'renew', 'failed'));
            throw new NotFoundHttpException("This membership type no longer exists");
        }

        $renewedMembership = $this->create($membership->getClient(), $membership->getPlan()->getId());

        $this->membershipLogger->info('Membership renewed', $this->membershipEventContext($this->membershipContext($renewedMembership, [
            'source_membership_id' => $membership->getId(),
        ]), 'renew', 'succeeded'));

        return $renewedMembership;
    }

    /**
     * @throws ExceptionInterface
     */
    public function terminate(Membership $membership): Membership
    {
        $context = $this->membershipContext($membership);

        $this->membershipLogger->info('Membership terminate started', $this->membershipEventContext($context, 'terminate', 'started'));

        if ($membership->getStatus() === MembershipStatusEnum::EXPIRED) {
            $this->membershipLogger->notice('Membership terminate skipped: membership already expired', $this->membershipEventContext($context, 'terminate', 'skipped'));
            throw new InvalidMembershipStatusException("Membership already expired");
        }

        $membership->setEndDate(new DateTimeImmutable());
        $membership->setStatus(MembershipStatusEnum::EXPIRED);

        $this->entityManager->flush();

        $this->membershipLogger->info('Membership terminated', $this->membershipEventContext($this->membershipContext($membership), 'terminate', 'succeeded'));

        $this->analyticsPublisher->publish(
            'membership.terminated',
            [
                'client_id' => $membership->getClient()->getId(),
                'membership_id' => $membership->getId(),
                'plan_id' => $membership->getPlan()->getId(),
                'price' => $membership->getPayment()->getAmount(),
                'payment_method' => $membership->getPayment()?->getMethod()->value ?? 'unknown',
            ]
        );

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

        $this->membershipLogger->info('Membership expiration scan started', $this->membershipEventContext([
            'domain' => 'membership',
            'evaluated_at' => $curDate->format(DATE_ATOM),
            'expired_candidates' => count($expiredMemberships),
        ], 'expire', 'started'));

        foreach ($expiredMemberships as $membership) {
            if ($membership->getStatus() === MembershipStatusEnum::ACTIVE && $membership->getEndDate() <= $curDate) {
                $membership->setStatus(MembershipStatusEnum::EXPIRED);

                $this->membershipLogger->info('Membership expired by scheduler', $this->membershipEventContext($this->membershipContext($membership, [
                    'evaluated_at' => $curDate->format(DATE_ATOM),
                ]), 'expire', 'succeeded'));
            }
        }

        $this->entityManager->flush();

        $this->membershipLogger->info('Membership expiration scan finished', $this->membershipEventContext([
            'domain' => 'membership',
            'evaluated_at' => $curDate->format(DATE_ATOM),
            'expired_count' => count($expiredMemberships),
        ], 'expire', 'completed'));

        return count($expiredMemberships);
    }

    private function membershipContext(Membership $membership, array $extra = []): array
    {
        return $this->membershipActionContext(
            membership: $membership,
            client: $membership->getClient(),
            extra: $extra,
        );
    }

    private function membershipActionContext(?Membership $membership = null, ?Client $client = null, array $extra = []): array
    {
        return $extra + [
            'domain' => 'membership',
            'membership_id' => $membership?->getId(),
            'client_id' => $client?->getId() ?? $membership?->getClient()?->getId(),
            'membership_plan_id' => $membership?->getPlan()?->getId(),
            'membership_plan_name' => $membership?->getPlan()?->getName(),
            'payment_id' => $membership?->getPayment()?->getId(),
            'payment_method' => $membership?->getPayment()?->getMethod()?->value,
            'payment_status' => $membership?->getPayment()?->getStatus()?->value,
            'status' => $membership?->getStatus()?->value,
            'visits' => $membership?->getVisits(),
            'session_limit' => $membership?->getSessionLimit(),
            'duration_days' => $membership?->getDurationDays(),
            'start_date' => $membership?->getStartDate()?->format(DATE_ATOM),
            'end_date' => $membership?->getEndDate()?->format(DATE_ATOM),
            'frozen_at' => $membership?->getFrozenAt()?->format(DATE_ATOM),
        ];
    }

    private function membershipEventContext(array $context, string $operation, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
            'operation' => $operation,
            'outcome' => $outcome,
        ];
    }
}
