<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1/pos')->middleware([
    'auth:sanctum',
    'user.active',
    'mfa.enrolled',
])->group(function (): void {
    Route::get('/health', fn () => ['status' => 'ok']);
});
