<?php

use App\Console\Commands\ImportLegacyContent;
use App\Console\Commands\MakeAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        ImportLegacyContent::class,
        MakeAdmin::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum-SPA-Modus: Anfragen von SANCTUM_STATEFUL_DOMAINS bekommen Session-Cookie und CSRF-Schutz
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
