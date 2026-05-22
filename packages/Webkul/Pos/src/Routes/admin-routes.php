<?php

use Illuminate\Support\Facades\Route;
use Webkul\Pos\Http\Controllers\Admin\BanksController;
use Webkul\Pos\Http\Controllers\Admin\BarcodeProductController;
use Webkul\Pos\Http\Controllers\Admin\CashMovementController;
use Webkul\Pos\Http\Controllers\Admin\FixedExpenseController;
use Webkul\Pos\Http\Controllers\Admin\OrdersController;
use Webkul\Pos\Http\Controllers\Admin\OutletsController;
use Webkul\Pos\Http\Controllers\Admin\ProductRequestController;
use Webkul\Pos\Http\Controllers\Admin\ProfitReportController;
use Webkul\Pos\Http\Controllers\Admin\ReceiptController;
use Webkul\Pos\Http\Controllers\Admin\ReportsController;
use Webkul\Pos\Http\Controllers\Admin\UsersController;
use Webkul\Pos\Http\Controllers\Admin\WasteController;

Route::group([
    'middleware' => ['admin', 'pos'],
    'prefix'     => config('app.admin_url').'/pos',
], function () {
    Route::redirect('/', config('app.admin_url').'/pos/outlets')->name('admin.pos.index');

    Route::controller(UsersController::class)->prefix('users')->group(function () {
        Route::get('', 'index')->name('admin.pos.users.index');

        Route::get('create', 'create')->name('admin.pos.users.create');

        Route::post('create', 'store')->name('admin.pos.users.store');

        Route::get('edit/{id}', 'edit')->name('admin.pos.users.edit');

        Route::put('edit/{id}', 'update')->name('admin.pos.users.update');

        Route::delete('edit/{id}', 'destroy')->name('admin.pos.users.delete');

        Route::post('mass-update', 'massUpdate')->name('admin.pos.users.mass_update');

        Route::post('mass-delete', 'massDestroy')->name('admin.pos.users.mass_delete');
    });

    Route::controller(OutletsController::class)->prefix('outlets')->group(function () {
        Route::get('', 'index')->name('admin.pos.outlets.index');

        Route::get('create', 'create')->name('admin.pos.outlets.create');

        Route::post('create', 'store')->name('admin.pos.outlets.store');

        Route::get('edit/{id}', 'edit')->name('admin.pos.outlets.edit');

        Route::put('edit/{id}', 'update')->name('admin.pos.outlets.update');

        Route::get('assign/{id}', 'assignProduct')->name('admin.pos.outlets.assign_products');

        Route::post('mass-assign/{id}', 'massAssign')->name('admin.pos.outlets.assign_products.mass_assign');

        Route::delete('edit/{id}', 'destroy')->name('admin.pos.outlets.delete');

        Route::post('mass-update', 'massUpdate')->name('admin.pos.outlets.mass_update');

        Route::post('mass-delete', 'massDestroy')->name('admin.pos.outlets.mass_delete');
    });

    Route::controller(BarcodeProductController::class)->prefix('products')->group(function () {
        Route::get('', 'index')->name('admin.pos.barcode_products.index');

        Route::get('generate-barcode/{id}', 'generateBarcode')->name('admin.pos.barcode_products.generate_barcode');

        Route::get('print-barcode/{id}', 'printBarcode')->name('admin.pos.barcode_products.print_barcode');

        Route::post('mass-generate-barcode', 'massGenerateBarcode')->name('admin.pos.barcode_products.mass_generate_barcode');

        Route::post('mass-print-barcode', 'massPrintBarcode')->name('admin.pos.barcode_products.mass_print_barcode');

        Route::post('barcode-type/{id}', 'updateBarcodeType')->name('admin.pos.barcode_products.update_barcode_type');

        Route::post('gtin/{id}', 'updateGtin')->name('admin.pos.barcode_products.update_gtin');
    });

    Route::get('orders', [OrdersController::class, 'index'])->name('admin.pos.orders.index');

    Route::controller(ProductRequestController::class)->prefix('requests')->group(function () {
        Route::get('', 'index')->name('admin.pos.requests.index');

        Route::get('view/{id}', 'view')->name('admin.pos.requests.view');

        Route::put('view/{id}', 'update')->name('admin.pos.requests.update');

        Route::post('mass-update', 'massUpdate')->name('admin.pos.requests.mass_update');
    });

    Route::controller(BanksController::class)->prefix('banks')->group(function () {
        Route::get('', 'index')->name('admin.pos.banks.index');

        Route::get('create', 'create')->name('admin.pos.banks.create');

        Route::post('create', 'store')->name('admin.pos.banks.store');

        Route::get('edit/{id}', 'edit')->name('admin.pos.banks.edit');

        Route::put('edit/{id}', 'update')->name('admin.pos.banks.update');

        Route::delete('edit/{id}', 'destroy')->name('admin.pos.banks.delete');

        Route::post('mass-delete', 'massDestroy')->name('admin.pos.banks.mass_delete');
    });

    Route::get('sales-report', [ReportsController::class, 'index'])->name('admin.pos.sales_report.index');

    Route::controller(ReceiptController::class)->prefix('receipts')->group(function () {
        Route::get('', 'index')->name('admin.pos.receipts.index');

        Route::get('create', 'create')->name('admin.pos.receipts.create');

        Route::post('create', 'store')->name('admin.pos.receipts.store');

        Route::get('edit/{id}', 'edit')->name('admin.pos.receipts.edit');

        Route::get('preview/{id}', 'show')->name('admin.pos.receipts.show');

        Route::post('edit/{id}', 'update')->name('admin.pos.receipts.update');

        Route::delete('edit/{id}', 'destroy')->name('admin.pos.receipts.delete');

        Route::post('mass-delete', 'massDestroy')->name('admin.pos.receipts.mass_delete');
    });

    Route::controller(WasteController::class)->prefix('waste')->group(function () {
        Route::get('', 'index')->name('admin.pos.waste.index');

        Route::get('{id}', 'show')->name('admin.pos.waste.verify');

        Route::post('{id}/verify', 'verify')->name('admin.pos.waste.do-verify');

        Route::delete('{id}', 'destroy')->name('admin.pos.waste.delete');

        Route::post('mass-delete', 'massDestroy')->name('admin.pos.waste.mass_delete');
    });

    Route::controller(CashMovementController::class)->prefix('cash-movements')->group(function () {
        Route::get('', 'index')->name('admin.pos.cash-movements.index');

        Route::get('create', 'create')->name('admin.pos.cash-movements.create');

        Route::post('create', 'store')->name('admin.pos.cash-movements.store');

        Route::delete('{id}', 'destroy')->name('admin.pos.cash-movements.delete');
    });

    Route::controller(FixedExpenseController::class)->prefix('fixed-expenses')->group(function () {
        Route::get('', 'index')->name('admin.pos.fixed-expenses.index');

        Route::get('create', 'create')->name('admin.pos.fixed-expenses.create');

        Route::post('create', 'store')->name('admin.pos.fixed-expenses.store');

        Route::get('edit/{id}', 'edit')->name('admin.pos.fixed-expenses.edit');

        Route::put('edit/{id}', 'update')->name('admin.pos.fixed-expenses.update');

        Route::delete('edit/{id}', 'destroy')->name('admin.pos.fixed-expenses.delete');

        Route::post('apply-month', 'applyMonth')->name('admin.pos.fixed-expenses.apply-month');
    });

    Route::controller(ProfitReportController::class)->prefix('profit-report')->group(function () {
        Route::get('', 'index')->name('admin.pos.profit-report.index');

        Route::get('data', 'getData')->name('admin.pos.profit-report.data');
    });
});
