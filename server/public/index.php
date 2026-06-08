<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Перманентный редирект тестового стенда на основной сайт.
// Срабатывает только по хосту тестового домена, поэтому на проде не зациклит.
if (($_SERVER['HTTP_HOST'] ?? '') === 'museum.in-site.ru') {
    header('Location: https://cadet-museum38.ru' . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
