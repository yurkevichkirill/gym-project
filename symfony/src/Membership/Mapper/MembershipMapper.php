<?php

declare(strict_types=1);

namespace App\Membership\Mapper;

use App\Membership\DTO\MembershipResponse;
use App\Membership\Entity\Membership;

class MembershipMapper implements MembershipMapperInterface
{
    public function map(Membership $membership): MembershipResponse
    {
        return MembershipResponse::fromEntity($membership);
    }
}
