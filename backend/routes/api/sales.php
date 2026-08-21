<?php

use App\Http\Controllers\Api\V1\SalesFulfillmentController;
use App\Http\Controllers\Api\V1\SalesOrderController;
use App\Http\Controllers\Api\V1\SalesOrderDecisionController;
use App\Http\Controllers\Api\V1\SalesOrderReservationController;
use App\Http\Controllers\Api\V1\SalesOrderSubmitController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/sales')
    ->middleware(['auth:sanctum', 'user.active', 'mfa.enrolled'])
    ->group(function (): void {
        Route::get('/orders', [SalesOrderController::class, 'index'])
            ->middleware('permission:sales.view');
        Route::post('/orders', [SalesOrderController::class, 'store'])
            ->middleware('permission:sales.manage');
        Route::get('/orders/{salesOrderPublicId}', [
            SalesOrderController::class, 'show',
        ])->middleware('permission:sales.view');

        Route::post('/orders/{salesOrderPublicId}/submit',
            SalesOrderSubmitController::class)
            ->middleware('permission:sales.submit');

        Route::post('/orders/{salesOrderPublicId}/approve', [
            SalesOrderDecisionController::class, 'approve',
        ])->middleware([
            'permission:sales.approve',
            'privileged.reason',
        ]);

        Route::post('/orders/{salesOrderPublicId}/reject', [
            SalesOrderDecisionController::class, 'reject',
        ])->middleware([
            'permission:sales.approve',
            'privileged.reason',
        ]);

        Route::get('/orders/{salesOrderPublicId}/reservation', [
            SalesOrderReservationController::class, 'show',
        ])->middleware('permission:sales.view');

        Route::post('/orders/{salesOrderPublicId}/reservation', [
            SalesOrderReservationController::class, 'reserve',
        ])->middleware('permission:inventory.reserve');

        Route::post('/orders/{salesOrderPublicId}/reservation/release', [
            SalesOrderReservationController::class, 'release',
        ])->middleware([
            'permission:inventory.reserve',
            'privileged.reason',
        ]);

        Route::post('/orders/{salesOrderPublicId}/fulfillments/pick', [
            SalesFulfillmentController::class, 'pick',
        ])->middleware([
            'permission:sales.manage',
            'permission:inventory.reserve',
        ]);

        Route::get('/fulfillments/{fulfillmentPublicId}', [
            SalesFulfillmentController::class, 'show',
        ])->middleware('permission:sales.view');

        Route::post('/fulfillments/{fulfillmentPublicId}/dispatch', [
            SalesFulfillmentController::class, 'dispatch',
        ])->middleware([
            'permission:sales.manage',
            'permission:inventory.reserve',
        ]);

        Route::post('/fulfillments/{fulfillmentPublicId}/reverse', [
            SalesFulfillmentController::class, 'reverse',
        ])->middleware([
            'permission:inventory.reverse',
            'privileged.reason',
        ]);
    });
