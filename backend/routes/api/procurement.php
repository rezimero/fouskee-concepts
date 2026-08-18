<?php

use App\Http\Controllers\Api\V1\GoodsReceiptController;
use App\Http\Controllers\Api\V1\GoodsReceiptDecisionController;
use App\Http\Controllers\Api\V1\GoodsReceiptReversalController;
use App\Http\Controllers\Api\V1\ProcurementMatchController;
use App\Http\Controllers\Api\V1\ProcurementMatchDecisionController;
use App\Http\Controllers\Api\V1\ProcurementPolicyController;
use App\Http\Controllers\Api\V1\PurchaseOrderCancelController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\PurchaseOrderDecisionController;
use App\Http\Controllers\Api\V1\PurchaseOrderSubmitController;
use App\Http\Controllers\Api\V1\SupplierInvoiceController;
use App\Http\Controllers\Api\V1\SupplierInvoiceMatchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/procurement')
    ->middleware(['auth:sanctum', 'user.active', 'mfa.enrolled'])
    ->group(function (): void {
        Route::get('/policy', [ProcurementPolicyController::class, 'show'])
            ->middleware('permission:procurement.view');
        Route::put('/policy', [ProcurementPolicyController::class, 'update'])
            ->middleware([
                'permission:procurement.manage',
                'privileged.reason',
            ]);

        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])
            ->middleware('permission:procurement.view');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])
            ->middleware('permission:procurement.manage');
        Route::get('/purchase-orders/{purchaseOrderPublicId}', [
            PurchaseOrderController::class, 'show',
        ])->middleware('permission:procurement.view');
        Route::post('/purchase-orders/{purchaseOrderPublicId}/submit',
            PurchaseOrderSubmitController::class)
            ->middleware('permission:procurement.submit');
        Route::post('/purchase-orders/{purchaseOrderPublicId}/approve', [
            PurchaseOrderDecisionController::class, 'approve',
        ])->middleware([
            'permission:procurement.approve',
            'privileged.reason',
        ]);
        Route::post('/purchase-orders/{purchaseOrderPublicId}/reject', [
            PurchaseOrderDecisionController::class, 'reject',
        ])->middleware([
            'permission:procurement.approve',
            'privileged.reason',
        ]);
        Route::post('/purchase-orders/{purchaseOrderPublicId}/cancel',
            PurchaseOrderCancelController::class)
            ->middleware([
                'permission:procurement.manage',
                'privileged.reason',
            ]);

        Route::get('/goods-receipts', [GoodsReceiptController::class, 'index'])
            ->middleware('permission:procurement.view');
        Route::post('/goods-receipts', [GoodsReceiptController::class, 'store'])
            ->middleware('permission:procurement.receive');
        Route::get('/goods-receipts/{goodsReceiptPublicId}', [
            GoodsReceiptController::class, 'show',
        ])->middleware('permission:procurement.view');
        Route::post('/goods-receipts/{goodsReceiptPublicId}/approve-exception', [
            GoodsReceiptDecisionController::class, 'approve',
        ])->middleware([
            'permission:procurement.exception.approve',
            'privileged.reason',
        ]);
        Route::post('/goods-receipts/{goodsReceiptPublicId}/reject-exception', [
            GoodsReceiptDecisionController::class, 'reject',
        ])->middleware([
            'permission:procurement.exception.approve',
            'privileged.reason',
        ]);
        Route::post('/goods-receipts/{goodsReceiptPublicId}/reverse',
            GoodsReceiptReversalController::class)
            ->middleware([
                'permission:procurement.receive',
                'permission:inventory.reverse',
                'privileged.reason',
            ]);

        Route::get('/supplier-invoices', [SupplierInvoiceController::class, 'index'])
            ->middleware('permission:procurement.view');
        Route::post('/supplier-invoices', [SupplierInvoiceController::class, 'store'])
            ->middleware('permission:procurement.invoice');
        Route::get('/supplier-invoices/{supplierInvoicePublicId}', [
            SupplierInvoiceController::class, 'show',
        ])->middleware('permission:procurement.view');
        Route::post('/supplier-invoices/{supplierInvoicePublicId}/match',
            SupplierInvoiceMatchController::class)
            ->middleware('permission:procurement.match');

        Route::get('/match-runs/{matchRunPublicId}', [
            ProcurementMatchController::class, 'show',
        ])->middleware('permission:procurement.view');
        Route::post('/match-runs/{matchRunPublicId}/approve-exception', [
            ProcurementMatchDecisionController::class, 'approve',
        ])->middleware([
            'permission:procurement.exception.approve',
            'privileged.reason',
        ]);
        Route::post('/match-runs/{matchRunPublicId}/reject-exception', [
            ProcurementMatchDecisionController::class, 'reject',
        ])->middleware([
            'permission:procurement.exception.approve',
            'privileged.reason',
        ]);
    });
