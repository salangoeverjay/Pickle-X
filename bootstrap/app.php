<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Vercel's (or any host's) proxy: trust X-Forwarded-* so URLs stay https.
        $middleware->trustProxies(at: '*');
        $middleware->alias(['role' => EnsureRole::class]);
        $middleware->redirectUsersTo(fn (Request $request) => route($request->user()->homeRoute()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Matches the Error ID people see on the 500 page, so a support message can be found in the logs.
        $exceptions->context(fn () => ['error_id' => error_id()]);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
