<?php

use Illuminate\Support\Facades\Route;
use Webkul\PayPay\Http\Controllers\PayPayController;

Route::middleware(['web'])->group(function () {
    Route::get('paypay/callback', [PayPayController::class, 'callback'])
        ->name('paypay.callback');

    Route::get('paypay/cancel', [PayPayController::class, 'cancel'])
        ->name('paypay.cancel');
});

Route::middleware(['web'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->group(function () {
        Route::post('paypay/webhook', [PayPayController::class, 'webhook'])
            ->name('paypay.webhook');
    });
