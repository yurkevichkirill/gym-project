<?php

declare(strict_types=1);

namespace App\User\Mapper;

use App\User\Entity\User;

interface UserMapperInterface
{
    public function map(User $user): UserResponse;
}
