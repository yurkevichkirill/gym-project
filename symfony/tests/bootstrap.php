<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__).'/.env');

$appDebug = $_SERVER['APP_DEBUG'] ?? false;
$appDebug = filter_var($appDebug, FILTER_VALIDATE_BOOL);

if ($appDebug) {
    umask(0000);
}
