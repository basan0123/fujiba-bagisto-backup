<?php

use Fujiba\PwaFrontend\Http\Controllers\Api\ConfigController;
use Fujiba\PwaFrontend\Http\Controllers\Api\PaymentController;
use Fujiba\PwaFrontend\Http\Controllers\Api\PostcodeController;
use Fujiba\PwaFrontend\Http\Controllers\Api\SearchController;
use Fujiba\PwaFrontend\Http\Controllers\Api\SwitcherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->prefix('api/v1')
    ->group(function () {
        Route::get('pwa-frontend/config', [ConfigController::class, 'index'])->name('pwa-frontend.config');
        Route::get('postcode/{code}',     [PostcodeController::class, 'show'])->name('pwa-frontend.postcode')
            ->where('code', '[0-9-]{7,8}');
        Route::get('pwa-frontend/search', SearchController::class)->name('pwa-frontend.search');

        Route::get('pwa-frontend/switch-currency', [SwitcherController::class, 'currency'])->name('pwa-frontend.switch-currency');
        Route::get('pwa-frontend/switch-locale',   [SwitcherController::class, 'locale'])->name('pwa-frontend.switch-locale');

        Route::get('pwa-frontend/payment-methods', PaymentController::class)->name('pwa-frontend.payment-methods');
    });
