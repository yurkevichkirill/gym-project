<?php

declare(strict_types=1);

namespace App\Membership\Mapper;

use App\Membership\DTO\MembershipResponse;
use App\Membership\Entity\Membership;

interface MembershipMapperInterface
{
    public function map(Membership $membership): MembershipResponse;
}
