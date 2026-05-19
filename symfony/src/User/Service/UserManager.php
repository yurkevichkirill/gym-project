<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\DTO\LoginUserRequestDTO;
use App\User\Entity\User;
use App\User\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserManager
{
    public function __construct(
        public UserRepository $repo,
        public UserPasswordHasherInterface $hasher,
    )
    {}

    /**
     * @throws UnauthorizedHttpException
     */
    public function login(?LoginUserRequestDTO $dto): User
    {
        $user = $this->repo->findOneBy(['email' => $dto->email ?? null]);

        if ($user === null || !$this->hasher->isPasswordValid($user, $dto->password ?? '')) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials');
        }

        if ($user->getDeletedAt() !== null) {
            throw new UnauthorizedHttpException('Bearer', 'User is deleted');
        }

        return $user;
    }
}
