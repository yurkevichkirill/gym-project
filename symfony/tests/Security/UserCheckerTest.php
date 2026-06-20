<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\UserChecker;
use App\User\Entity\User;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserCheckerTest extends TestCase
{
    #[DataProvider('checkMethodProvider')]
    public function testActiveAppUserIsAllowed(string $method): void
    {
        $checker = new UserChecker();

        $checker->{$method}($this->activeUser());

        self::addToAssertionCount(1);
    }

    #[DataProvider('checkMethodProvider')]
    public function testNonAppUserInterfaceIsAllowed(string $method): void
    {
        $checker = new UserChecker();

        $checker->{$method}(new class implements UserInterface {
            public function getRoles(): array
            {
                return ['ROLE_EXTERNAL'];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return 'external-user';
            }
        });

        self::addToAssertionCount(1);
    }

    /**
     * @param callable(User): void $mutate
     */
    #[DataProvider('rejectedUserProvider')]
    public function testRejectedAppUserStates(string $method, callable $mutate, string $expectedMessage): void
    {
        $user = $this->activeUser();
        $mutate($user);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage($expectedMessage);

        (new UserChecker())->{$method}($user);
    }

    #[DataProvider('checkMethodProvider')]
    public function testDeletedStateHasPriorityOverBlockedAndInactive(string $method): void
    {
        $user = $this->activeUser();
        $user->setDeletedAt(new DateTime('2026-01-01 00:00:00'));
        $user->setBlockedAt(new DateTimeImmutable('2026-01-02 00:00:00'));
        $user->setIsActive(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('User account is deleted.');

        (new UserChecker())->{$method}($user);
    }

    #[DataProvider('checkMethodProvider')]
    public function testBlockedStateHasPriorityOverInactive(string $method): void
    {
        $user = $this->activeUser();
        $user->setBlockedAt(new DateTimeImmutable('2026-01-02 00:00:00'));
        $user->setIsActive(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('User account is blocked.');

        (new UserChecker())->{$method}($user);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function checkMethodProvider(): iterable
    {
        yield 'pre auth' => ['checkPreAuth'];
        yield 'post auth' => ['checkPostAuth'];
    }

    /**
     * @return iterable<string, array{string, callable(User): void, string}>
     */
    public static function rejectedUserProvider(): iterable
    {
        foreach (self::checkMethodProvider() as $methodName => [$method]) {
            yield $methodName . ' deleted' => [
                $method,
                static function (User $user): void {
                    $user->setDeletedAt(new DateTime('2026-01-01 00:00:00'));
                },
                'User account is deleted.',
            ];

            yield $methodName . ' blocked' => [
                $method,
                static function (User $user): void {
                    $user->setBlockedAt(new DateTimeImmutable('2026-01-01 00:00:00'));
                },
                'User account is blocked.',
            ];

            yield $methodName . ' inactive' => [
                $method,
                static function (User $user): void {
                    $user->setIsActive(false);
                },
                'User account is not active.',
            ];
        }
    }

    private function activeUser(): User
    {
        return (new User())
            ->setEmail('active@example.com')
            ->setFirstName('Active')
            ->setLastName('User')
            ->setPhone('+10000000000')
            ->setIsActive(true);
    }
}
