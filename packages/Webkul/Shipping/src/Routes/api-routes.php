<?php

use Illuminate\Support\Facades\Route;
use Webkul\Shipping\Http\Controllers\Api\ShippingCalculatorController;

Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'api/v1',
], function () {

    Route::post(
        'cart/{cartId}/shipping/calculate',
        [ShippingCalculatorController::class, 'calculate']
    )->name('api.v1.shipping.calculate');

});
