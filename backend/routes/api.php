<?php

use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\SecuritySessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'user.active'])
    ->group(function (): void {
        Route::get('/me', MeController::class)->name('api.v1.me');

        Route::middleware('mfa.enrolled')->group(function (): void {
            Route::get(
                '/security/sessions',
                [SecuritySessionController::class, 'index'],
            )->name('api.v1.security.sessions.index');

            Route::delete(
                '/security/sessions/{sessionReference}',
                [SecuritySessionController::class, 'destroy'],
            )
                ->middleware('privileged.reason')
                ->name('api.v1.security.sessions.destroy');
        });
    });
