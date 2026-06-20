<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
$maintenancePath = file_exists(__DIR__.'/../core/storage/framework/maintenance.php') 
    ? __DIR__.'/../core/storage/framework/maintenance.php' 
    : __DIR__.'/../storage/framework/maintenance.php';

if (file_exists($maintenancePath)) {
    require $maintenancePath;
}

// Register the Composer autoloader...
$autoloadPath = file_exists(__DIR__.'/../core/vendor/autoload.php')
    ? __DIR__.'/../core/vendor/autoload.php'
    : __DIR__.'/../vendor/autoload.php';
require $autoloadPath;

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$appPath = file_exists(__DIR__.'/../core/bootstrap/app.php')
    ? __DIR__.'/../core/bootstrap/app.php'
    : __DIR__.'/../bootstrap/app.php';
$app = require_once $appPath;

// Fix public path for cPanel
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
