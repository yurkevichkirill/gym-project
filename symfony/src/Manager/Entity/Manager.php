<?php

declare(strict_types=1);

namespace App\Manager\Entity;

use App\Manager\Repository\ManagerRepository;
use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManagerRepository::class)]
final class Manager extends User
{
    public function __construct()
    {
    }
}
