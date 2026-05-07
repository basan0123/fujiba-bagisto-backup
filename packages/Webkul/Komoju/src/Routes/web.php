<?php

use Illuminate\Support\Facades\Route;
use Webkul\Komoju\Http\Controllers\KomojuController;

Route::middleware(['web'])->group(function () {
    Route::get('komoju/callback', [KomojuController::class, 'callback'])
        ->name('komoju.callback');

    Route::get('komoju/cancel', [KomojuController::class, 'cancel'])
        ->name('komoju.cancel');
});

Route::middleware(['web'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->group(function () {
        Route::post('komoju/webhook', [KomojuController::class, 'webhook'])
            ->name('komoju.webhook');
    });
