<?php

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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.access' => \App\Http\Middleware\EnsureAdminAccess::class,
            'admin.ability' => \App\Http\Middleware\EnsureAdminAbility::class,
            'catalog.feature' => \App\Http\Middleware\EnsureCatalogFeatureEnabled::class,
            'user.feature' => \App\Http\Middleware\EnsureUserFeatureEnabled::class,
            'api.user.enabled' => \App\Http\Middleware\EnsureApiUserEnabled::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
