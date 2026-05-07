<?php

use Fujiba\InventoryControl\Http\Controllers\Admin\BarcodeAssignController;
use Fujiba\InventoryControl\Http\Controllers\Admin\BatchController;
use Fujiba\InventoryControl\Http\Controllers\Admin\ExpiryAlertController;
use Fujiba\InventoryControl\Http\Controllers\Admin\StockTakeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'admin', 'auth:admin'])
    ->prefix(config('app.admin_url') . '/inventory-control')
    ->name('admin.inventory_control.')
    ->group(function () {
        Route::get('expiry-alerts', [ExpiryAlertController::class, 'index'])->name('expiry_alerts.index');
        Route::post('expiry-alerts/{batch}/mark-removed', [ExpiryAlertController::class, 'markRemoved'])->name('expiry_alerts.mark_removed');

        Route::get('batches', [BatchController::class, 'index'])->name('batches.index');
        Route::get('batches/create', [BatchController::class, 'create'])->name('batches.create');
        Route::post('batches', [BatchController::class, 'store'])->name('batches.store');
    });

Route::middleware(['web', 'admin', 'auth:admin'])
    ->prefix(config('app.admin_url') . '/catalog/stock-take')
    ->name('admin.inventory_control.stock_take.')
    ->group(function () {
        Route::get('/', [StockTakeController::class, 'index'])->name('index');
        Route::get('create', [StockTakeController::class, 'create'])->name('create');
        Route::post('/', [StockTakeController::class, 'store'])->name('store');
        Route::get('{id}', [StockTakeController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('{id}/scan', [StockTakeController::class, 'scan'])->whereNumber('id')->name('scan');
        Route::post('{id}/items', [StockTakeController::class, 'saveItem'])->whereNumber('id')->name('save_item');
        Route::post('{id}/quick-increment', [StockTakeController::class, 'quickIncrement'])->whereNumber('id')->name('quick_increment');
        Route::post('{id}/batches', [StockTakeController::class, 'saveBatch'])->whereNumber('id')->name('save_batch');
        Route::post('{id}/batches/create', [StockTakeController::class, 'createBatch'])->whereNumber('id')->name('create_batch');
        Route::delete('{id}/batches/{batchRowId}', [StockTakeController::class, 'deleteBatch'])->whereNumber('id')->whereNumber('batchRowId')->name('delete_batch');
        Route::get('{id}/baseline-status', [StockTakeController::class, 'baselineStatus'])->whereNumber('id')->name('baseline_status');
        Route::get('{id}/unscanned-batches', [StockTakeController::class, 'unscannedBatches'])->whereNumber('id')->name('unscanned_batches');
        Route::post('{id}/mark-expired-waste', [StockTakeController::class, 'markExpiredAsWaste'])->whereNumber('id')->name('mark_expired_waste');
        Route::delete('{id}/items/{itemId}', [StockTakeController::class, 'deleteItem'])->whereNumber('id')->whereNumber('itemId')->name('delete_item');
        Route::get('{id}/summary', [StockTakeController::class, 'summary'])->whereNumber('id')->name('summary');
        Route::post('{id}/bulk-channel', [StockTakeController::class, 'bulkChannel'])->whereNumber('id')->name('bulk_channel');
        Route::get('{id}/products', [StockTakeController::class, 'productsForSession'])->whereNumber('id')->name('products');
        Route::get('{id}/search-products', [StockTakeController::class, 'searchProducts'])->whereNumber('id')->name('search_products');
        Route::post('{id}/link-barcode', [StockTakeController::class, 'linkBarcode'])->whereNumber('id')->name('link_barcode');
        Route::get('{id}/export', [StockTakeController::class, 'export'])->whereNumber('id')->name('export');
        Route::post('{id}/apply', [StockTakeController::class, 'apply'])->whereNumber('id')->name('apply');
        Route::post('{id}/cancel', [StockTakeController::class, 'cancel'])->whereNumber('id')->name('cancel');
    });

Route::middleware(['web', 'admin', 'auth:admin'])
    ->prefix(config('app.admin_url') . '/catalog/barcode-assign')
    ->name('admin.barcode_assign.')
    ->group(function () {
        Route::get('/', [BarcodeAssignController::class, 'index'])->name('index');
        Route::post('lookup', [BarcodeAssignController::class, 'lookup'])->name('lookup');
        Route::get('search', [BarcodeAssignController::class, 'search'])->name('search');
        Route::post('assign', [BarcodeAssignController::class, 'assign'])->name('assign');
        Route::post('undo', [BarcodeAssignController::class, 'undo'])->name('undo');
    });
