<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'auth.session' => \App\Http\Middleware\CheckSessionAuth::class,
            'student'=> \App\Http\Middleware\StudentMiddleware::class,
            'lecture'=> \App\Http\Middleware\LectureMiddleware::class,
            'hod'=> \App\Http\Middleware\HodMiddleware::class,
            'dean'=> \App\Http\Middleware\DeanMiddleware::class,
            'rector'=> \App\Http\Middleware\RectorMiddleware::class,
            'registrar'=> \App\Http\Middleware\RegistrarMiddleware::class,
            'admin'=> \App\Http\Middleware\AdminMiddleware::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();