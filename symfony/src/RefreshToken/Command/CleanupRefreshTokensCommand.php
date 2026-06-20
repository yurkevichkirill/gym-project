<?php

declare(strict_types=1);

namespace App\RefreshToken\Command;

use App\RefreshToken\Repository\RefreshTokenRepository;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'app:refresh-tokens:cleanup')]
final readonly class CleanupRefreshTokensCommand
{
    public function __construct(
        private RefreshTokenRepository $refreshTokenRepository,
    )
    {}

    public function __invoke(): int
    {
        $this->refreshTokenRepository->removeExpiredAndStaleRevoked(
            now: new DateTimeImmutable(),
            revokedBefore: new DateTimeImmutable('-7 days'),
        );

        return Command::SUCCESS;
    }
}
