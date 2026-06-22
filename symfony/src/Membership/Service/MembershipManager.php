<?php

declare(strict_types=1);

namespace App\Membership\Service;

use App\Client\Entity\Client;
use App\Infrastructure\ClickHouse\Publisher\AnalyticsPublisher;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Exception\InvalidMembershipStatusException;
use App\Membership\Exception\MembershipActiveException;
use App\Membership\Repository\MembershipRepository;
use App\MembershipPlan\Exception\MembershipPlanNotFoundException;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Service\PaymentSettlementService;
use App\User\Exception\UserNotFoundException;
use App\User\Service\AvailabilityService;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Throwable;

final readonly class MembershipManager
{
    public function __construct(
        private MembershipRepository $membershipRepo,
        private MembershipPlanRepository $membershipPlanRepo,
        private EntityManagerInterface $entityManager,
        private AvailabilityService $userAvailabilityService,
        private PaymentSettlementService $paymentSettlementService,
        private LoggerInterface $membershipLogger,
        private AnalyticsPublisher $analyticsPublisher,
    )
    {}

    /**
     * @throws DateMalformedStringException
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
            $this->userAvailabilityService->ensureActive($client);

            $plan = $this->membershipPlanRepo->find($membershipPlanId);
            if ($plan === null) {
                throw new MembershipPlanNotFoundException();
            }

            $clientId = $client->getId();

            $membership = $this->entityManager->wrapInTransaction(function () use ($clientId, $plan) {
                $lockedClient = $this->entityManager->find(
                    Client::class,
                    $clientId,
                    LockMode::PESSIMISTIC_WRITE
                );

                if ($lockedClient === null) {
                    throw new UserNotFoundException('Client not found');
                }

                $blockingMembership = $this->membershipRepo->findBlockingMembership($lockedClient);
                if ($blockingMembership !== null) {
                    throw new MembershipActiveException("Client already has {$blockingMembership->getStatus()->value} membership");
                }

                $membership = new Membership();
                $membership->setClient($lockedClient);
                $membership->setPlan($plan);
                $membership->setName($plan->getName());
                $membership->setDurationDays($plan->getDurationDays());
                $membership->setSessionLimit($plan->getSessionLimit());

                $this->membershipRepo->create($membership);

                $price = $plan->getPrice();

                $this->paymentSettlementService->createMembershipPayment(
                    $lockedClient,
                    $price,
                    $membership,
                );

                return $membership;
            });

            try {
                $this->analyticsPublisher->publish('membership.created', [
                    'client_id' => $client->getId(),
                    'membership_id' => $membership->getId(),
                    'plan_id' => $membership->getPlan()?->getId(),
                    'price' => $plan->getPrice(),
                    'payment_method' => $membership->getPayment()?->getMethod()->value ?? 'unknown',
                ]);
            } catch (Throwable $e) {
                $this->membershipLogger->warning('membership.analytics_failed',
                    $this->membershipEventContext($loggingContext, 'create', 'analytics_failed', [
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                    ])
                );
            }

            return $membership;
        } catch (MembershipPlanNotFoundException $e) {
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
     * @throws Throwable
     * @throws RandomException
     * @throws ExceptionInterface
     */
    public function cancel(Membership $membership): Membership
    {
        $loggingContext = [
            'membership_id' => $membership->getId(),
            'client_id' => $membership->getClient()->getId(),
        ];

        try {
            if ($membership->getStatus() !== MembershipStatusEnum::PENDING) {
                throw new InvalidMembershipStatusException('Only pending memberships can be canceled');
            }

            $paymentId = $membership->getPayment()?->getId();
            if ($paymentId === null) {
                throw new InvalidMembershipStatusException('Membership is not fully initialized');
            }

                $updatedMembership = $this->paymentSettlementService->withLockedPayment(
                $paymentId,
                function (Payment $lockedPayment): Membership {
                    if ($lockedPayment->getStatus() !== PaymentStatusEnum::PENDING) {
                        throw new InvalidMembershipStatusException('Associated payment is no longer pending. Cannot cancel.');
                    }

                    $membership = $lockedPayment->getMembership();

                    if ($membership === null) {
                        throw new InvalidMembershipStatusException('Membership not found on locked payment');
                    }

                    $message = $this->paymentSettlementService
                        ->cancelPayment($lockedPayment);

                    $membership->cancel(MembershipStatusEnum::CANCELED_BY_CLIENT);

                    $this->paymentSettlementService->dispatchPaymentMessage($message);

                    return $membership;
                }
            );

            try {
                $this->analyticsPublisher->publish(
                    'membership.canceled',
                    [
                        'client_id' => $updatedMembership->getClient()->getId(),
                        'membership_id' => $updatedMembership->getId(),
                        'plan_id' => $updatedMembership->getPlan()?->getId(),
                        'price' => $updatedMembership->getPayment()?->getAmount(),
                        'payment_method' => $updatedMembership->getPayment()?->getMethod()->value,
                    ]
                );
            } catch (Throwable $e) {
                $this->membershipLogger->warning('membership.analytics_failed',
                    $this->membershipEventContext($loggingContext, 'cancel', 'analytics_failed', [
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                    ])
                );
            }

            return $updatedMembership;

        } catch (InvalidMembershipStatusException $e) {
            $this->membershipLogger->notice('membership.cancel.rejected',
                $this->membershipEventContext($loggingContext, 'cancel', 'rejected', [
                    'reason' => $e::class,
                ])
            );

            throw $e;
        } catch (Throwable $e) {
            $this->membershipLogger->error('membership.cancel.failed',
                $this->membershipEventContext($loggingContext, 'cancel', 'failed', [
                    'error' => $e->getMessage(),
                    'exception_class' => $e::class,
                ])
            );

            throw $e;
        }
    }

    /**
     * @throws DateMalformedStringException
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

            $payment = $membership->getPayment();
            if ($payment === null) {
                throw new InvalidMembershipStatusException('Membership is not fully initialized');
            }

            $membership->setFrozenAt(new DateTimeImmutable());
            $membership->setStatus(MembershipStatusEnum::FROZEN);

            $this->entityManager->flush();

            try {
                $this->analyticsPublisher->publish(
                'membership.froze',
                [
                    'client_id' => $membership->getClient()->getId(),
                    'membership_id' => $membership->getId(),
                    'plan_id' => $membership->getPlan()?->getId(),
                    'price' => $payment->getAmount(),
                    'payment_method' => $payment->getMethod()->value,
                ]
            );
            } catch (Throwable $e) {
                $this->membershipLogger->warning('membership.analytics_failed',
                    $this->membershipEventContext($loggingContext, 'freeze', 'analytics_failed', [
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                    ])
                );
            }

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
     * @throws DateMalformedStringException
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
            if ($membership->getStatus() !== MembershipStatusEnum::FROZEN) {
                throw new InvalidMembershipStatusException('Only frozen membership can be unfrozen');
            }

            $frozenAt = $membership->getFrozenAt();
            $endDate = $membership->getEndDate();
            $payment = $membership->getPayment();
            if ($frozenAt === null || $endDate === null || $payment === null) {
                throw new InvalidMembershipStatusException('Membership is not fully initialized');
            }

            $dateInterval = $frozenAt->diff(new DateTimeImmutable());
            $membership->setEndDate($endDate->add($dateInterval));
            $membership->setFrozenAt(null);
            $membership->setStatus(MembershipStatusEnum::ACTIVE);

            $this->entityManager->flush();

            $this->membershipLogger->info('membership.unfreeze.succeeded',
                $this->membershipEventContext($loggingContext, 'unfreeze', 'succeeded')
            );

            try {
                $this->analyticsPublisher->publish(
                    'membership.unfroze',
                    [
                        'client_id' => $membership->getClient()->getId(),
                        'membership_id' => $membership->getId(),
                        'plan_id' => $membership->getPlan()?->getId(),
                        'price' => $payment->getAmount(),
                        'payment_method' => $payment->getMethod()->value,
                    ]
                );
            } catch (Throwable $e) {
                $this->membershipLogger->warning('membership.analytics_failed',
                    $this->membershipEventContext($loggingContext, 'unfreeze', 'analytics_failed', [
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                    ])
                );
            }

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
            $this->userAvailabilityService->ensureNotBlocked($membership->getClient());
            $this->userAvailabilityService->ensureActive($membership->getClient());

            $payment = $membership->getPayment();
            if ($payment === null) {
                throw new InvalidMembershipStatusException('Membership is not fully initialized');
            }

            $clientId = $membership->getClient()->getId();
            $plan = $membership->getPlan();
            $price = $payment->getAmount();
            $name = $membership->getName();
            $durationDays = $membership->getDurationDays();
            $sessionLimit = $membership->getSessionLimit();

            $renewedMembership = $this->entityManager->wrapInTransaction(function () use ($clientId, $plan, $price, $name, $durationDays, $sessionLimit): Membership {
                $lockedClient = $this->entityManager->find(
                    Client::class,
                    $clientId,
                    LockMode::PESSIMISTIC_WRITE
                );

                if ($lockedClient === null) {
                    throw new UserNotFoundException('Client not found');
                }

                $blockingMembership = $this->membershipRepo->findBlockingMembership($lockedClient);
                if ($blockingMembership !== null) {
                    throw new MembershipActiveException("Client already has {$blockingMembership->getStatus()->value} membership");
                }

                $renewedMembership = new Membership();
                $renewedMembership->setClient($lockedClient);
                $renewedMembership->setPlan($plan);
                $renewedMembership->setName($name);
                $renewedMembership->setDurationDays($durationDays);
                $renewedMembership->setSessionLimit($sessionLimit);

                $this->membershipRepo->create($renewedMembership);
                $this->paymentSettlementService->createMembershipPayment(
                    $lockedClient,
                    $price,
                    $renewedMembership,
                );

                return $renewedMembership;
            });

            try {
                $this->analyticsPublisher->publish('membership.renewed', [
                    'client_id' => $renewedMembership->getClient()->getId(),
                    'membership_id' => $renewedMembership->getId(),
                    'plan_id' => $renewedMembership->getPlan()?->getId(),
                    'price' => $renewedMembership->getPayment()?->getAmount(),
                    'payment_method' => $renewedMembership->getPayment()?->getMethod()->value ?? 'unknown',
                ]);
            } catch (Throwable $e) {
                $this->membershipLogger->warning('membership.analytics_failed',
                    $this->membershipEventContext($loggingContext, 'renew', 'analytics_failed', [
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                    ])
                );
            }

            return $renewedMembership;
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
     * @throws DateMalformedStringException
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

            $payment = $membership->getPayment();
            if ($payment === null) {
                throw new InvalidMembershipStatusException('Membership is not fully initialized');
            }

            $membership->setEndDate(new DateTimeImmutable());
            $membership->setStatus(MembershipStatusEnum::EXPIRED);

            $this->entityManager->flush();

            try {
                $this->analyticsPublisher->publish(
                    'membership.terminated',
                    [
                        'client_id' => $membership->getClient()->getId(),
                        'membership_id' => $membership->getId(),
                        'plan_id' => $membership->getPlan()?->getId(),
                        'price' => $payment->getAmount(),
                        'payment_method' => $payment->getMethod()->value,
                    ]
                );
            } catch (Throwable $e) {
                $this->membershipLogger->warning('membership.analytics_failed',
                    $this->membershipEventContext($loggingContext, 'terminate', 'analytics_failed', [
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                    ])
                );
            }

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

    /**
     * @param array<string, scalar|null> $context
     * @param array<string, scalar|null> $extra
     * @return array<string, scalar|null>
     */
    private function membershipEventContext(array $context, string $operation, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
            'domain' => 'membership',
            'operation' => $operation,
            'outcome' => $outcome,
        ];
    }
}
