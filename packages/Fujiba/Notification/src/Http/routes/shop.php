<?php

use Fujiba\Notification\Http\Controllers\Shop\OrderTrackController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'signed'])
    ->get('/order-track/{orderId}', OrderTrackController::class)
    ->where('orderId', '[0-9]+')
    ->name('fujiba.notification.order.track');
