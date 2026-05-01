<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // API stateless (prefix `/api`) untuk konsumsi third-party via
        // Personal Access Token Sanctum. Terpisah dari web.php agar middleware
        // stack & naming route tidak tercampur dengan SPA flow.
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        // App ini SPA murni — tidak ada route bernama `login` di Laravel.
        // Guest yang hit halaman HTML ber-auth (mis. Swagger UI) diarahkan ke
        // path SPA `/auth/signin` yang di-handle Vue Router.
        // Request ber-`Accept: application/json` tetap dapat 401 JSON.
        $middleware->redirectGuestsTo('/auth/signin');

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'custom_permission' => \App\Http\Middleware\CustomPermissionMiddleware::class,
            'check_token_idle' => \App\Http\Middleware\CheckTokenIdle::class,
            'api_client.active' => \App\Http\Middleware\EnforceApiClientActive::class,
            'api_client.origin' => \App\Http\Middleware\EnforceApiClientOrigin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
