<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/bootstrap.php')) {
    require __DIR__ . '/bootstrap.php';
} elseif (class_exists(Dotenv::class) && file_exists(__DIR__ . '/../.env')) {
    new Dotenv()->bootEnv(__DIR__ . '/../.env');
}

$appEnv = $_SERVER['APP_ENV'] ?? 'test';
if (!is_string($appEnv)) {
    $appEnv = 'test';
}

$appDebug = $_SERVER['APP_DEBUG'] ?? true;
$appDebug = filter_var($appDebug, FILTER_VALIDATE_BOOL);

$kernel = new Kernel($appEnv, $appDebug);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
