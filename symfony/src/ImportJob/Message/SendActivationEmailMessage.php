<?php

declare(strict_types=1);

namespace App\ImportJob\Message;

final readonly class SendActivationEmailMessage
{
    public function __construct(
        public int $clientId
    )
    {}
}
