<?php

declare(strict_types=1);

namespace App\Payment\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class InsufficientFundsException extends BadRequestHttpException
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(string $message = 'Insufficient funds for buying', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
