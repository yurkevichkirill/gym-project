<?php

declare(strict_types=1);

namespace App\User\DTO;

use App\User\Entity\User;
use LogicException;

final readonly class CurrentUserResponseDTO
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public int $id,
        public string $email,
        public array $roles,
    ) {}

    public static function fromEntity(User $user): self
    {
        $id = $user->getId();
        if ($id === null) {
            throw new LogicException('User is not persisted.');
        }

        /** @var list<string> $roles */
        $roles = array_values($user->getRoles());

        return new self(
            id: $id,
            email: $user->getEmail(),
            roles: $roles,
        );
    }
}
