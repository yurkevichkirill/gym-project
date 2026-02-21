<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class InsufficientFundsException extends BadRequestHttpException
{
    public function __construct(string $message = 'Insufficient funds for buying', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
