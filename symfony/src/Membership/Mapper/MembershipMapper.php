<?php

declare(strict_types=1);

namespace App\Membership\Mapper;

use App\Membership\DTO\MembershipResponseDTO;
use App\Membership\Entity\Membership;

class MembershipMapper implements MembershipMapperInterface
{
    public function map(Membership $membership): MembershipResponseDTO
    {
        return MembershipResponseDTO::fromEntity($membership);
    }
}
