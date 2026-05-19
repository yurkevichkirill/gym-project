<?php

declare(strict_types=1);

namespace App\Admin\Entity;

use App\Admin\Repository\AdminRepository;
use App\User\Entity\User;
use App\User\Enum\UserRolesEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
final class Admin extends User
{
    public function __construct()
    {
        $this->setRoles([UserRolesEnum::ROLE_ADMIN->value]);
    }

    public function getRoles(): array
    {
        $roles = parent::getRoles();
        $adminRole = UserRolesEnum::ROLE_ADMIN->value;

        if (!in_array($adminRole, $roles, true)) {
            $roles[] = $adminRole;
        }
        return array_unique($roles);
    }
}
