<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        $timezone = $_SERVER['APP_TIMEZONE'] ?? $_ENV['APP_TIMEZONE'] ?? 'UTC';

        if (
            !is_string($timezone)
            || !in_array($timezone, timezone_identifiers_list(), true)
        ) {
            throw new InvalidArgumentException('APP_TIMEZONE must contain a valid IANA timezone');
        }

        date_default_timezone_set($timezone);

        parent::__construct($environment, $debug);
    }
}
