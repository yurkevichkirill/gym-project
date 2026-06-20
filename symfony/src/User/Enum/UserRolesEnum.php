<?php

declare(strict_types=1);

namespace App\User\Enum;

enum UserRolesEnum: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_CLIENT = 'ROLE_CLIENT';
    case ROLE_TRAINER = 'ROLE_TRAINER';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_MANAGER = 'ROLE_MANAGER';
}
