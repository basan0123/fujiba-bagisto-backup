<?php

use Fujiba\SagawaDelivery\Http\Controllers\Api\TrackingController;
use Fujiba\SagawaDelivery\Http\Controllers\DeliverySlotController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('sagawa')->group(function () {
    Route::get('delivery-slots', DeliverySlotController::class)
        ->name('sagawa.delivery_slots');
});

Route::middleware(['web'])->prefix('api/v1/sagawa')->group(function () {
    Route::get('tracking', TrackingController::class)
        ->name('sagawa.api.tracking');
});
