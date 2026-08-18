<?php

use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CatalogReferenceController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\InventoryAdjustmentController;
use App\Http\Controllers\Api\V1\InventoryBalanceController;
use App\Http\Controllers\Api\V1\InventoryLocationController;
use App\Http\Controllers\Api\V1\InventoryMovementController;
use App\Http\Controllers\Api\V1\InventoryReceiptController;
use App\Http\Controllers\Api\V1\InventoryReservationController;
use App\Http\Controllers\Api\V1\InventoryReservationReleaseController;
use App\Http\Controllers\Api\V1\InventoryReversalController;
use App\Http\Controllers\Api\V1\InventoryTransferController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PriceListController;
use App\Http\Controllers\Api\V1\PriceListItemController;
use App\Http\Controllers\Api\V1\PriceListItemIndexController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SecuritySessionController;
use App\Http\Controllers\Api\V1\SupplierController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'user.active'])
    ->group(function (): void {
        Route::get('/me', MeController::class)->name('api.v1.me');

        Route::middleware('mfa.enrolled')->group(function (): void {
            Route::get('/security/sessions', [
                SecuritySessionController::class,
                'index',
            ])->name('api.v1.security.sessions.index');

            Route::delete('/security/sessions/{sessionReference}', [
                SecuritySessionController::class,
                'destroy',
            ])
                ->middleware('privileged.reason')
                ->name('api.v1.security.sessions.destroy');

            Route::prefix('master-data')->group(function (): void {
                Route::get('/customers', [CustomerController::class, 'index'])
                    ->middleware('permission:customers.view');
                Route::post('/customers', [CustomerController::class, 'store'])
                    ->middleware('permission:customers.manage');

                Route::get('/suppliers', [SupplierController::class, 'index'])
                    ->middleware('permission:suppliers.view');
                Route::post('/suppliers', [SupplierController::class, 'store'])
                    ->middleware('permission:suppliers.manage');

                Route::get('/products', [ProductController::class, 'index'])
                    ->middleware('permission:catalog.view');
                Route::post('/products', [ProductController::class, 'store'])
                    ->middleware('permission:catalog.manage');

                Route::get('/catalog/references', CatalogReferenceController::class)
                    ->middleware('permission:catalog.view');
                Route::post('/brands', [BrandController::class, 'store'])
                    ->middleware('permission:catalog.manage');
                Route::post('/categories', [
                    ProductCategoryController::class,
                    'store',
                ])->middleware('permission:catalog.manage');

                Route::get('/price-lists', [PriceListController::class, 'index'])
                    ->middleware('permission:pricing.view');
                Route::post('/price-lists', [PriceListController::class, 'store'])
                    ->middleware('permission:pricing.manage');

                Route::get(
                    '/price-lists/{priceListPublicId}/items',
                    PriceListItemIndexController::class,
                )->middleware('permission:pricing.view');

                Route::put(
                    '/price-lists/{priceListPublicId}/items/{packagingPublicId}',
                    PriceListItemController::class,
                )->middleware([
                    'permission:pricing.manage',
                    'privileged.reason',
                ]);
            });

            Route::prefix('inventory')->group(function (): void {
                Route::get('/locations', [InventoryLocationController::class, 'index'])
                    ->middleware('permission:inventory.view');
                Route::post('/locations', [InventoryLocationController::class, 'store'])
                    ->middleware('permission:inventory.locations.manage');
                Route::get('/balances', InventoryBalanceController::class)
                    ->middleware('permission:inventory.view');
                Route::get('/movements', [InventoryMovementController::class, 'index'])
                    ->middleware('permission:inventory.view');
                Route::get('/movements/{movementPublicId}', [
                    InventoryMovementController::class,
                    'show',
                ])->middleware('permission:inventory.view');

                Route::post('/receipts', [InventoryReceiptController::class, 'store'])
                    ->middleware('permission:inventory.receive');
                Route::post('/transfers', [InventoryTransferController::class, 'store'])
                    ->middleware('permission:inventory.transfer');
                Route::post('/adjustments', [InventoryAdjustmentController::class, 'store'])
                    ->middleware(['permission:inventory.adjust', 'privileged.reason']);
                Route::post('/movements/{movementPublicId}/reversals', [
                    InventoryReversalController::class,
                    'store',
                ])->middleware(['permission:inventory.reverse', 'privileged.reason']);

                Route::post('/reservations', [InventoryReservationController::class, 'store'])
                    ->middleware('permission:inventory.reserve');
                Route::post('/reservations/{reservationPublicId}/release',
                    InventoryReservationReleaseController::class)
                    ->middleware('permission:inventory.reserve');
            });
        });
    });
