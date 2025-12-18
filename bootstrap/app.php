<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Poll all tenants every 60 seconds for FortiGate clients
        $schedule->command('tenants:poll-fortigate')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Calculate hourly analytics for all tenants every 15 minutes
        $schedule->command('tenants:calculate-analytics hourly')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Generate daily reports at 2am
        $schedule->command('tenants:generate-reports')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
