<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/bootstrap.php')) {
    require __DIR__ . '/bootstrap.php';
} elseif (class_exists(Dotenv::class) && file_exists(__DIR__.'/../.env')) {
    new Dotenv()->bootEnv(__DIR__.'/../.env');
}

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'test', (bool) ($_SERVER['APP_DEBUG'] ?? true));

return new Application($kernel);
