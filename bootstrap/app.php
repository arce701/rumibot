<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\SetCurrentTenant;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetLogContext;
use App\Http\Middleware\SetTenantFromToken;
use App\Services\Tenant\TenantContext;
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
        $middleware->web(append: [
            SetCurrentTenant::class,
            SetLocale::class,
            SetLogContext::class,
        ]);

        $middleware->api(append: [
            SetLogContext::class,
        ]);

        $middleware->alias([
            'tenant' => EnsureTenantContext::class,
            'super-admin' => EnsureSuperAdmin::class,
            'tenant.api' => SetTenantFromToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->context(fn () => [
            'tenant_id' => app(TenantContext::class)->getTenantId(),
            'user_id' => auth()->id(),
        ]);
    })->create();
