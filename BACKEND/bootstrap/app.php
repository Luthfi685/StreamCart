<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'   => \App\Http\Middleware\CheckRole::class,
            'banned' => \App\Http\Middleware\CheckBanned::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\CheckBanned::class);
        $middleware->appendToGroup('api', \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
        $middleware->appendToGroup('api',  \App\Http\Middleware\CheckBanned::class);

        // Nonaktifkan CSRF untuk API & Broadcasting karena Ionic menggunakan Bearer Token (Stateless)
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'broadcasting/auth',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
