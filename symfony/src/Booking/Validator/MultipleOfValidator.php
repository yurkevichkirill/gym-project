<?php

declare(strict_types=1);

namespace App\Booking\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class MultipleOfValidator extends ConstraintValidator
{
    /**
     * @inheritDoc
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MultipleOf) {
            throw new UnexpectedTypeException($constraint, MultipleOf::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $number = (int) round((float) $value);
        $remainder = $number % $constraint->multiple;

        if ($remainder !== 0) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
