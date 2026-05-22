<?php

use Fujiba\Dashboard\Http\Controllers\Admin\BusinessOverviewController;
use Fujiba\Dashboard\Http\Controllers\Admin\CartActivityController;
use Fujiba\Dashboard\Http\Controllers\Admin\DashboardFilterStateController;
use Fujiba\Dashboard\Http\Controllers\Admin\InventoryValueController;
use Fujiba\Dashboard\Http\Controllers\Admin\OrdersByRegionController;
use Fujiba\Dashboard\Http\Controllers\Admin\PaymentMethodsController;
use Fujiba\Dashboard\Http\Controllers\Admin\SalesByStateController;
use Fujiba\Dashboard\Http\Controllers\Admin\ShippingCostImportController;
use Fujiba\Dashboard\Http\Controllers\Admin\ShippingPerformanceController;
use Fujiba\Dashboard\Http\Controllers\Admin\SubscribersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'admin', 'auth:admin'])
    ->prefix(config('app.admin_url') . '/fujiba/dashboard')
    ->name('admin.fujiba.dashboard.')
    ->group(function () {
        Route::get('inventory-value', [InventoryValueController::class, 'index'])
            ->name('inventory_value');

        Route::get('business-overview', [BusinessOverviewController::class, 'index'])
            ->name('business_overview');

        Route::get('cart-activity', [CartActivityController::class, 'index'])
            ->name('cart_activity');

        Route::get('cart-activity/{cart}', [CartActivityController::class, 'show'])
            ->whereNumber('cart')
            ->name('cart_activity_detail');

        Route::get('filter-state', [DashboardFilterStateController::class, 'show'])
            ->name('filter_state.show');

        Route::post('filter-state', [DashboardFilterStateController::class, 'store'])
            ->name('filter_state.store');

        Route::get('payment-methods', [PaymentMethodsController::class, 'index'])
            ->name('payment_methods');

        Route::get('orders-by-region', [OrdersByRegionController::class, 'index'])
            ->name('orders_by_region');

        Route::get('sales-by-state', [SalesByStateController::class, 'index'])
            ->name('sales_by_state');

        Route::get('subscribers', [SubscribersController::class, 'index'])
            ->name('subscribers');

        Route::get('shipping-performance', [ShippingPerformanceController::class, 'index'])
            ->name('shipping_performance');

        Route::get('shipping-costs', [ShippingCostImportController::class, 'index'])
            ->name('shipping_costs.index');

        Route::post('shipping-costs/import', [ShippingCostImportController::class, 'import'])
            ->name('shipping_costs.import');

        Route::post('shipping-costs/order/{order}', [ShippingCostImportController::class, 'upsertManual'])
            ->whereNumber('order')
            ->name('shipping_costs.manual');
    });
