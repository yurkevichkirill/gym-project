<?php

declare(strict_types=1);

namespace App\Manager\Entity;

use App\Manager\Repository\ManagerRepository;
use App\User\Entity\User;
use App\User\Enum\UserRolesEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManagerRepository::class)]
final class Manager extends User
{
    public function __construct()
    {
        parent::__construct();
        $this->setRoles([UserRolesEnum::ROLE_MANAGER->value]);
    }

    public function getRoles(): array
    {
        $roles = parent::getRoles();
        $managerRole = UserRolesEnum::ROLE_MANAGER->value;

        if (!in_array($managerRole, $roles, true)) {
            $roles[] = $managerRole;
        }

        return array_unique($roles);
    }
}
