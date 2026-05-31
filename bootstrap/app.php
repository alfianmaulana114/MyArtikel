<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthorMiddleware;
use App\Http\Middleware\HtmlSanitizationMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\RedirectIfAdmin;
use App\Http\Middleware\RedirectIfNotAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api-summaries.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'author' => AuthorMiddleware::class,
            'security' => SecurityHeaders::class,
            'rate.limit' => RateLimitMiddleware::class,
            'sanitize.html' => HtmlSanitizationMiddleware::class,
            'redirect.admin' => RedirectIfAdmin::class,
            'redirect.not.admin' => RedirectIfNotAdmin::class,
        ]);

        // Global middleware
        $middleware->append(SecurityHeaders::class);
        $middleware->append(HtmlSanitizationMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
