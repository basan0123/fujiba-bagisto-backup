<?php

use Fujiba\TikTokShop\Http\Controllers\Admin\TikTokSettingsController;
use Fujiba\TikTokShop\Http\Controllers\Admin\TikTokSyncController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'admin', 'auth:admin'])
    ->prefix(config('app.admin_url') . '/tiktok')
    ->name('admin.tiktok.')
    ->group(function () {
        Route::get('/',                [TikTokSettingsController::class, 'index'])->name('index');
        Route::post('/credentials',    [TikTokSettingsController::class, 'saveCredentials'])->name('credentials.save');
        Route::get('/connect',         [TikTokSettingsController::class, 'connect'])->name('connect');
        Route::get('/callback',        [TikTokSettingsController::class, 'callback'])->name('callback');
        Route::post('/disconnect',     [TikTokSettingsController::class, 'disconnect'])->name('disconnect');
        Route::post('/sync/products',              [TikTokSyncController::class, 'products'])->name('sync.products');
        Route::post('/sync/products/retry-failed', [TikTokSyncController::class, 'retryFailed'])->name('sync.products.retry_failed');
    });
