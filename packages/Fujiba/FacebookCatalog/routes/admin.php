<?php

use Fujiba\FacebookCatalog\Http\Controllers\Admin\ApprovalController;
use Fujiba\FacebookCatalog\Http\Controllers\Admin\SettingsController;
use Fujiba\FacebookCatalog\Http\Controllers\Admin\SyncController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'admin', 'auth:admin'])
    ->prefix(config('app.admin_url') . '/facebook-catalog')
    ->name('admin.facebook_catalog.')
    ->group(function () {
        Route::get('/',              [SettingsController::class, 'index'])->name('index');
        Route::post('/settings',     [SettingsController::class, 'save'])->name('settings.save');
        Route::post('/test',         [SettingsController::class, 'test'])->name('settings.test');
        Route::post('/disable',      [SettingsController::class, 'disable'])->name('settings.disable');

        Route::get('/products',      [ApprovalController::class, 'index'])->name('products.index');
        Route::post('/products/toggle',     [ApprovalController::class, 'toggle'])->name('products.toggle');
        Route::post('/products/bulk',       [ApprovalController::class, 'bulkApprove'])->name('products.bulk');

        Route::post('/sync',         [SyncController::class, 'syncAll'])->name('sync.all');
        Route::post('/sync/retry',   [SyncController::class, 'retryFailed'])->name('sync.retry');
    });
