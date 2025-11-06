<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            require __DIR__ . '/../routes/internal.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'authenticated' => \App\Http\Middleware\AuthenticatedMiddleware::class,
            'internal' => \App\Http\Middleware\AuthenticateInternal::class,
            'cookie.auth' => \App\Http\Middleware\SanctumCookieAuth::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();