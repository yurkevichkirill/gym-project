<?php

declare(strict_types=1);

namespace App\Membership\Mapper;

use App\Membership\DTO\MembershipResponseDTO;
use App\Membership\Entity\Membership;

interface MembershipMapperInterface
{
    public function map(Membership $membership): MembershipResponseDTO;
}
