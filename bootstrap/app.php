<?php

use App\Http\Middleware\EnsureAuthentication;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            // Register Nexo API key middleware so routes can use ->middleware('nexo.api')
            'nexo.api' => \App\Http\Middleware\VerifyApiKey::class
        ]);

        $middleware->validateCsrfTokens(except: [

            '/auth/callback',

        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
