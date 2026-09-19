<?php

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
        $middleware->alias([
            'admin' => App\Http\Middleware\EnsureUserIsAdmin::class,
            'delivery' => App\Http\Middleware\EnsureUserIsDeliveryPartner::class,
            'active' => App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        // There is no route named `login`: each audience has its own way in
        // (ADR-004), so a guest is sent to the one that belongs to the page.
        $middleware->redirectGuestsTo(fn (Request $request): string => match (true) {
            $request->is('admin', 'admin/*') => route('filament.admin.auth.login'),
            $request->is('delivery', 'delivery/*') => route('delivery.login'),
            default => route('auth.login'),
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
