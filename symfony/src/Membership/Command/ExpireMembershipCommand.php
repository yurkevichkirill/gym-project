<?php

declare(strict_types=1);

namespace App\Membership\Command;

use App\Membership\Service\MembershipExpirationService;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:membership:expire')]
final readonly class ExpireMembershipCommand
{
    public function __construct(
        private MembershipExpirationService $service,
    )
    {}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->service->expire();
        $io->success("Updated {$count} expired memberships");

        return Command::SUCCESS;
    }
}
