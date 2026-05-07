<?php

use Fujiba\SagawaDelivery\Http\Controllers\Admin\CredentialController;
use Fujiba\SagawaDelivery\Http\Controllers\Admin\ScheduleController;
use Fujiba\SagawaDelivery\Http\Controllers\Admin\ShipmentQueueController;
use Fujiba\SagawaDelivery\Http\Controllers\Admin\UsageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/sagawa')
    ->name('admin.sagawa.')
    ->group(function () {
        Route::get('/', [ShipmentQueueController::class, 'index'])->name('queue.index');
        Route::get('/shipments/{shipment}', [ShipmentQueueController::class, 'show'])->name('queue.show');

        Route::post('/shipments/{shipment}/address-check', [ShipmentQueueController::class, 'addressCheck'])->name('queue.address-check');
        Route::post('/shipments/{shipment}/verify', [ShipmentQueueController::class, 'verify'])->name('queue.verify');
        Route::post('/shipments/{shipment}/reject', [ShipmentQueueController::class, 'reject'])->name('queue.reject');
        Route::post('/shipments/{shipment}/reissue', [ShipmentQueueController::class, 'reissue'])->name('queue.reissue');
        Route::get('/shipments/{shipment}/label', [ShipmentQueueController::class, 'printLabel'])->name('queue.label');
        Route::post('/shipments/{shipment}/sync-tracking', [ShipmentQueueController::class, 'syncTracking'])->name('queue.sync-tracking');

        Route::get('/usage', [UsageController::class, 'index'])->name('usage');

        Route::get('/credentials',            [CredentialController::class, 'index'])->name('credentials.index');
        Route::post('/credentials',           [CredentialController::class, 'save'])->name('credentials.save');
        Route::delete('/credentials/{id}',    [CredentialController::class, 'destroy'])->name('credentials.destroy');

        Route::get('/schedule',  [ScheduleController::class, 'index'])->name('schedule.index');
        Route::post('/schedule', [ScheduleController::class, 'save'])->name('schedule.save');
    });
