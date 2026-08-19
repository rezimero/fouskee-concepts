<?php

use App\Http\Controllers\Api\V1\SalesQuotationController;
use App\Http\Controllers\Api\V1\SalesQuotationLifecycleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/sales/quotations')
    ->middleware(['auth:sanctum', 'user.active', 'mfa.enrolled'])
    ->group(function (): void {
        Route::get('/', [SalesQuotationController::class, 'index'])
            ->middleware('permission:sales.view');
        Route::post('/', [SalesQuotationController::class, 'store'])
            ->middleware('permission:sales.manage');
        Route::get('/{quotationPublicId}', [
            SalesQuotationController::class, 'show',
        ])->middleware('permission:sales.view');

        Route::post('/{quotationPublicId}/send', [
            SalesQuotationLifecycleController::class, 'send',
        ])->middleware('permission:sales.manage');

        Route::post('/{quotationPublicId}/accept', [
            SalesQuotationLifecycleController::class, 'accept',
        ])->middleware('permission:sales.manage');

        Route::post('/{quotationPublicId}/convert', [
            SalesQuotationLifecycleController::class, 'convert',
        ])->middleware('permission:sales.manage');
    });
