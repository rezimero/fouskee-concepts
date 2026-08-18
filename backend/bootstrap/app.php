<?php

use App\Exceptions\InventoryDomainException;
use App\Http\Middleware\EnsureMfaEnrolled;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\RequirePrivilegedReason;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->throttleApi();

        $middleware->alias([
            'user.active' => EnsureUserIsActive::class,
            'mfa.enrolled' => EnsureMfaEnrolled::class,
            'permission' => RequirePermission::class,
            'privileged.reason' => RequirePrivilegedReason::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            InventoryDomainException $exception,
            Request $request,
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode,
            ], $exception->status);
        });
    })->create();
