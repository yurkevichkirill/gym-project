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

    /**
     * @throws \DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    public function create(Client $client, int $membershipPlanId): Membership
    {
        $loggingContext = [
            'client_id' => $client->getId(),
            'membership_plan_id' => $membershipPlanId,
        ];

        try {
            $this->userAvailabilityService->ensureNotBlocked($client);

            $plan = $this->membershipPlanRepo->find($membershipPlanId);
            if (!$plan) {
                throw new NotFoundHttpException('Membership plan not found');
            }

            return $this->entityManager->wrapInTransaction(function () use ($client, $plan, $loggingContext) {

                if ($this->visitingService->hasActiveMembership($client)) {
                    throw new MembershipActiveException();
                }

                $membership = new Membership();
                $membership->setClient($client);
                $membership->setPlan($plan);
                $membership->setName($plan->getName());
                $membership->setDurationDays($plan->getDurationDays());
                $membership->setSessionLimit($plan->getSessionLimit());

                $this->membershipRepo->create($membership);

                $price = $plan->getPrice();

                if ($client->getBalance() >= $price) {
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
                    $payment = $this->paymentService->createPayment(
                        $client,
                        $price,
                        PaymentCategoryEnum::MEMBERSHIP,
                        PaymentMethodEnum::CARD,
                    );
                }

                $membership->setPayment($payment);

                $this->entityManager->flush();

                $this->membershipLogger->info('membership.succeeded',
                    $this->membershipEventContext($loggingContext, 'create', 'succeeded', [
                        'membership_id' => $membership->getId(),
                        'price' => $price,
                    ])
                );

                $this->analyticsPublisher->publish('membership.created', [
                    'client_id' => $client->getId(),
                    'membership_id' => $membership->getId(),
                    'plan_id' => $plan->getId(),
                    'price' => $price,
                    'payment_method' => $membership->getPayment()?->getMethod()->value ?? 'unknown',
                ]);

                return $membership;
            });

        } catch (MembershipActiveException|NotFoundHttpException $e) {
            $this->membershipLogger->notice('membership.rejected',
                $this->membershipEventContext($loggingContext, 'create', 'rejected', [
                    'reason' => $e::class,
                ])
            );

            throw $e;

        } catch (Throwable $e) {
            $this->membershipLogger->error('membership.failed',
                $this->membershipEventContext($loggingContext, 'create', 'failed', [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                ])
            );

            throw $e;
        }
    }

    /**
     * @throws \DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    public function freeze(Membership $membership): Membership
    {
        $loggingContext = [
            'membership_id' => $membership->getId(),
            'client_id' => $membership->getClient()->getId(),
        ];

        try {
            if ($membership->getStatus() !== MembershipStatusEnum::ACTIVE) {
                throw new InvalidMembershipStatusException();
            }

            $membership->setFrozenAt(new DateTimeImmutable());
            $membership->setStatus(MembershipStatusEnum::FROZEN);

            $this->entityManager->flush();

            $this->membershipLogger->info('membership.freeze.succeeded',
                $this->membershipEventContext($loggingContext, 'freeze', 'succeeded')
            );

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
        } catch (InvalidMembershipStatusException $e) {
            $this->membershipLogger->notice('membership.freeze.rejected',
                $this->membershipEventContext($loggingContext, 'freeze', 'rejected', [
                    'reason' => $e::class,
                ])
            );

            throw $e;

        } catch (Throwable $e) {
            $this->membershipLogger->error('membership.freeze.failed',
                $this->membershipEventContext($loggingContext, 'freeze', 'failed', [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                ])
            );

            throw $e;
        }
    }

    /**
     * @throws \DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    public function unfreeze(Membership $membership): Membership
    {
        $loggingContext = [
            'membership_id' => $membership->getId(),
            'client_id' => $membership->getClient()->getId(),
        ];

        try {
            if ($membership->getStatus() != MembershipStatusEnum::FROZEN) {
                throw new InvalidMembershipStatusException("Only frozen membership can be unfrozen");
            }

            $dateInterval = $membership->getFrozenAt()->diff(new DateTimeImmutable());
            $membership->setEndDate($membership->getEndDate()->add($dateInterval));
            $membership->setFrozenAt(null);
            $membership->setStatus(MembershipStatusEnum::ACTIVE);

            $this->entityManager->flush();

            $this->membershipLogger->info('membership.unfreeze.succeeded',
                $this->membershipEventContext($loggingContext, 'unfreeze', 'succeeded')
            );

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
        } catch (InvalidMembershipStatusException $e) {
            $this->membershipLogger->notice('membership.unfreeze.rejected',
                $this->membershipEventContext($loggingContext, 'unfreeze', 'rejected', [
                    'reason' => $e::class,
                ])
            );

            throw $e;

        } catch (Throwable $e) {
            $this->membershipLogger->error('membership.unfreeze.failed',
                $this->membershipEventContext($loggingContext, 'unfreeze', 'failed', [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                ])
            );

            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function renew(Membership $membership): Membership
    {
        $loggingContext = [
            'membership_id' => $membership->getId(),
            'client_id' => $membership->getClient()->getId(),
        ];

        try {
            if (!$membership->getPlan()) {
                throw new NotFoundHttpException();
            }

            $new = $this->create($membership->getClient(), $membership->getPlan()->getId());

            $this->membershipLogger->info('membership.renew.succeeded',
                $this->membershipEventContext($loggingContext, 'renew', 'succeeded', [
                    'new_membership_id' => $new->getId(),
                ])
            );

            return $new;

        } catch (Throwable $e) {
            $this->membershipLogger->error('membership.renew.failed',
                $this->membershipEventContext($loggingContext, 'renew', 'failed', [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                ])
            );

            throw $e;
        }
    }

    /**
     * @throws \DateMalformedStringException
     * @throws Throwable
     * @throws ExceptionInterface
     */
    public function terminate(Membership $membership): Membership
    {
        $loggingContext = [
            'membership_id' => $membership->getId(),
            'client_id' => $membership->getClient()->getId(),
        ];

        try {
            if ($membership->getStatus() === MembershipStatusEnum::EXPIRED) {
                throw new InvalidMembershipStatusException('Membership already expired');
            }

            $membership->setEndDate(new DateTimeImmutable());
            $membership->setStatus(MembershipStatusEnum::EXPIRED);

            $this->entityManager->flush();

            $this->membershipLogger->info('membership.terminate.succeeded',
                $this->membershipEventContext($loggingContext, 'terminate', 'succeeded')
            );

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
        } catch (InvalidMembershipStatusException $e) {
            $this->membershipLogger->notice('membership.terminate.rejected',
                $this->membershipEventContext($loggingContext, 'terminate', 'rejected', [
                    'reason' => $e::class,
                ])
            );

            throw $e;

        } catch (Throwable $e) {
            $this->membershipLogger->error('membership.terminate.failed',
                $this->membershipEventContext($loggingContext, 'terminate', 'failed', [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                ])
            );

            throw $e;
        }
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

        $this->membershipLogger->info('membership.expire.succeeded', [
            'count' => count($expiredMemberships),
        ]);

        return count($expiredMemberships);
    }

    private function membershipEventContext(array $context, string $operation, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
            'domain' => 'membership',
            'operation' => $operation,
            'outcome' => $outcome,
        ];
    }
}
