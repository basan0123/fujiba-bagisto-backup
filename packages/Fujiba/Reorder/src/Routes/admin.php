<?php

use Fujiba\Reorder\Http\Controllers\Admin\ReorderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'admin', 'auth:admin'])
    ->prefix('admin/inventory/reorder')
    ->name('admin.inventory.reorder.')
    ->group(function () {
        Route::get('/', [ReorderController::class, 'index'])->name('index');
        Route::get('/products', [ReorderController::class, 'products'])->name('products');

        // @deprecated 2026-04-27 — legacy reorder_todos endpoints.
        // Replaced by /batches (store) and /batches/{id}/confirm-receive (arrive).
        // Scheduled for removal after 2026-05-11; printLabels stays until the
        // new batch flow grows label support.
        Route::post('/', [ReorderController::class, 'store'])->name('store');
        Route::post('/{id}/arrive', [ReorderController::class, 'arrive'])->name('arrive');
        Route::get('/labels', [ReorderController::class, 'printLabels'])->name('labels');

        // Clipboard hand-off from /admin/catalog/products list view.
        Route::post('/from-clipboard', [ReorderController::class, 'fromClipboard'])->name('from-clipboard');

        // Batches.
        Route::get('/batches', [ReorderController::class, 'listBatches'])->name('batches.index');
        Route::post('/batches', [ReorderController::class, 'storeBatch'])->name('batches.store');
        Route::get('/batches/{id}.json', [ReorderController::class, 'showBatch'])->name('batches.show');
        Route::put('/batches/{id}', [ReorderController::class, 'updateBatch'])->name('batches.update');

        // Full-screen pages.
        Route::get('/history', [ReorderController::class, 'historyIndex'])->name('history');
        Route::get('/batches/{id}/edit', [ReorderController::class, 'editBatch'])->name('batches.edit');
        Route::get('/batches/{id}/receive', [ReorderController::class, 'receiveScreen'])->name('batches.receive-screen');

        // Receive flow: start → save draft → confirm.
        Route::post('/batches/{id}/start-receive', [ReorderController::class, 'startReceive'])->name('batches.start-receive');
        Route::post('/batches/{id}/receive-draft', [ReorderController::class, 'saveReceiveDraft'])->name('batches.receive-draft');
        Route::post('/batches/{id}/confirm-receive', [ReorderController::class, 'confirmReceive'])->name('batches.confirm-receive');

        // Backward-compat shortcut from the index page's inline Receive button.
        Route::post('/batches/{id}/receive', [ReorderController::class, 'receiveBatch'])->name('batches.receive');
    });
