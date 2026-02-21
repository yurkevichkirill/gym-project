<?php

declare(strict_types=1);

namespace App\Booking\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class MultipleOf30 extends Constraint
{
    public function __construct(
        public string $message = 'Value should be multiple of 30',
        public int $multiple = 30,
        ?array $groups = null,
        mixed $payload = null,
    )
    {
        parent::__construct([], $groups, $payload);
    }
}
