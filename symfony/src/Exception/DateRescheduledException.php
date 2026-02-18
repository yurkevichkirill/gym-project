<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;
use Throwable;

class DateRescheduledException  extends Exception
{
    public function __construct(string $message = "This date cannot be selected", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
