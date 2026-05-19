<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\Exception\UnexpectedValueException;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class RequestCorrelationProcessor
{
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    /**
     * @throws UnexpectedValueException
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return $record;
        }

        $requestId = $request->attributes->getString('_request_id', '');
        $correlationId = $request->attributes->getString('_correlation_id', $requestId);

        if ($requestId === '' && $correlationId === '') {
            return $record;
        }

        return $record->with(extra: $record->extra + [
            'request_id' => $requestId !== '' ? $requestId : null,
            'correlation_id' => $correlationId !== '' ? $correlationId : null,
        ]);
    }
}
