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
    /**
     * @param callable(UserChecker, UserInterface): void $check
     */
    #[DataProvider('checkMethodProvider')]
    public function testActiveAppUserIsAllowed(callable $check): void
    {
        $check(new UserChecker(), $this->activeUser());

        self::addToAssertionCount(1);
    }

    /**
     * @param callable(UserChecker, UserInterface): void $check
     */
    #[DataProvider('checkMethodProvider')]
    public function testNonAppUserInterfaceIsAllowed(callable $check): void
    {
        $check(new UserChecker(), new class implements UserInterface {
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
     * @param callable(UserChecker, UserInterface): void $check
     * @param callable(User): void $mutate
     */
    #[DataProvider('rejectedUserProvider')]
    public function testRejectedAppUserStates(callable $check, callable $mutate, string $expectedMessage): void
    {
        $user = $this->activeUser();
        $mutate($user);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage($expectedMessage);

        $check(new UserChecker(), $user);
    }

    /**
     * @param callable(UserChecker, UserInterface): void $check
     */
    #[DataProvider('checkMethodProvider')]
    public function testDeletedStateHasPriorityOverBlockedAndInactive(callable $check): void
    {
        $user = $this->activeUser();
        $user->setDeletedAt(new DateTime('2026-01-01 00:00:00'));
        $user->setBlockedAt(new DateTimeImmutable('2026-01-02 00:00:00'));
        $user->setIsActive(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('User account is deleted.');

        $check(new UserChecker(), $user);
    }

    /**
     * @param callable(UserChecker, UserInterface): void $check
     */
    #[DataProvider('checkMethodProvider')]
    public function testBlockedStateHasPriorityOverInactive(callable $check): void
    {
        $user = $this->activeUser();
        $user->setBlockedAt(new DateTimeImmutable('2026-01-02 00:00:00'));
        $user->setIsActive(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('User account is blocked.');

        $check(new UserChecker(), $user);
    }

    /**
     * @return iterable<string, array{callable(UserChecker, UserInterface): void}>
     */
    public static function checkMethodProvider(): iterable
    {
        yield 'pre auth' => [static function (UserChecker $checker, UserInterface $user): void {
            $checker->checkPreAuth($user);
        }];

        yield 'post auth' => [static function (UserChecker $checker, UserInterface $user): void {
            $checker->checkPostAuth($user);
        }];
    }

    /**
     * @return iterable<string, array{callable(UserChecker, UserInterface): void, callable(User): void, string}>
     */
    public static function rejectedUserProvider(): iterable
    {
        foreach (self::checkMethodProvider() as $methodName => [$check]) {
            yield $methodName . ' deleted' => [
                $check,
                static function (User $user): void {
                    $user->setDeletedAt(new DateTime('2026-01-01 00:00:00'));
                },
                'User account is deleted.',
            ];

            yield $methodName . ' blocked' => [
                $check,
                static function (User $user): void {
                    $user->setBlockedAt(new DateTimeImmutable('2026-01-01 00:00:00'));
                },
                'User account is blocked.',
            ];

            yield $methodName . ' inactive' => [
                $check,
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
