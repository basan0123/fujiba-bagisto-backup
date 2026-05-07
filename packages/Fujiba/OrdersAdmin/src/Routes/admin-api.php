<?php

use Fujiba\OrdersAdmin\Http\Controllers\EstimateController;
use Fujiba\OrdersAdmin\Http\Controllers\OrderAddressController;
use Fujiba\OrdersAdmin\Http\Controllers\OrderFulfillmentController;
use Fujiba\OrdersAdmin\Http\Controllers\OrderItemEditController;
use Fujiba\OrdersAdmin\Http\Controllers\OrderLabelPrintedController;
use Fujiba\OrdersAdmin\Http\Controllers\OrderListController;
use Fujiba\OrdersAdmin\Http\Controllers\OrderNoteController;
use Fujiba\OrdersAdmin\Http\Controllers\OrderPaymentConfirmController;
use Fujiba\OrdersAdmin\Http\Controllers\OrderPaymentMethodController;
use Fujiba\OrdersAdmin\Http\Controllers\OrderStatusController;
use Fujiba\OrdersAdmin\Http\Controllers\OrderTrackingController;
use Illuminate\Support\Facades\Route;

/**
 * Admin in-session JSON endpoints for the orders advanced view.
 *
 * Mounted under the admin URL (config('app.admin_url')) so the Vue
 * component on the existing /admin/sales/orders page can call these
 * with the existing session + CSRF token, no Sanctum plumbing.
 */
Route::group([
    'middleware' => ['web', 'admin'],
    'prefix'     => config('app.admin_url') . '/api/orders',
], function () {
    Route::get('',                     [OrderListController::class,          'index']);
    Route::get('summary',              [OrderListController::class,          'summary']);
    Route::get('avatars',              [OrderListController::class,          'avatars']);
    Route::post('{id}/status',         [OrderStatusController::class,        'update']);
    Route::post('{id}/note',           [OrderNoteController::class,          'update']);
    Route::post('{id}/payment-method', [OrderPaymentMethodController::class, 'update']);
    Route::post('{id}/address',        [OrderAddressController::class,       'update']);
    Route::patch('{id}/tracking',           [OrderTrackingController::class,     'update']);
    Route::post('{id}/label-printed',       [OrderLabelPrintedController::class, 'flip']);
    Route::post('{id}/fulfillment/preview', [OrderFulfillmentController::class,    'preview']);
    Route::post('{id}/fulfillment',         [OrderFulfillmentController::class,    'update']);
    Route::post('{id}/payment-confirm',     [OrderPaymentConfirmController::class, 'update']);

    Route::get('{id}/edit-items',           [OrderItemEditController::class, 'show']);
    Route::get('{id}/edit-items/search',    [OrderItemEditController::class, 'searchProducts']);
    Route::post('{id}/edit-items',          [OrderItemEditController::class, 'update']);
});

/**
 * Sales estimate page + its in-page JSON helpers. Page route lives next
 * to /admin/sales/orders so menu-bar links and navigation feel native.
 * 'web' is required (cookies/CSRF); 'admin' enforces admin guard; without
 * 'auth:admin' the page silently 302s back to dashboard.
 */
Route::group([
    'middleware' => ['web', 'admin'],
    'prefix'     => config('app.admin_url') . '/sales',
], function () {
    Route::get('estimate', [EstimateController::class, 'index'])->name('admin.sales.estimate');
});

Route::group([
    'middleware' => ['web', 'admin'],
    'prefix'     => config('app.admin_url') . '/api/estimate',
], function () {
    Route::get('abandoned', [EstimateController::class, 'abandoned']);
});
