<?php

namespace App\Admin\Entity;

use App\Admin\Repository\AdminRepository;
use App\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
class Admin extends User
{
    public function __construct()
    {
        $this->setRoles(['ROLE_ADMIN']);
    }

    public function getRoles(): array
    {
        $roles = parent::getRoles();
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_ADMIN';
        }
        return array_unique($roles);
    }
}
