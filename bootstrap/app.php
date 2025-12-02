<?php

use App\Services\EventService;
use App\Services\DocumentService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

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
    })
    ->withSchedule(function (Schedule $schedule) {
        // Mark events as completed daily at 1:00 AM
        $schedule->call(function () {
            app(EventService::class)->markEventAsCompleted();
        })->dailyAt('01:00');

        // Perform daily backup at 2:00 AM
        $schedule->command('backup:dump')->dailyAt('08:21');

        // Purge documents older than 4 years daily at 3:00 AM
        $schedule->call(function () {
            app(DocumentService::class)->purgeOldDocuments();
        })->dailyAt('03:00');

        // $schedule->exec('freshclam')->weeklyOn(6, '00:00'); // Commented out since freshclam is running on the background as a daemon
    })
    ->create();
