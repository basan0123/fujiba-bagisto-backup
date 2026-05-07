<?php

use Illuminate\Support\Facades\Route;
use Webkul\Waste\Http\Controllers\Admin\WasteController;

Route::group([
    'middleware' => ['web', 'admin'],
    'prefix'     => config('app.admin_url'),
], function () {

    Route::prefix('catalog/waste')->name('admin.catalog.waste.')->group(function () {

        Route::get('/',             [WasteController::class, 'index'])->name('index');
        Route::post('/store',       [WasteController::class, 'store'])->name('store');
        Route::get('/dashboard',    [WasteController::class, 'dashboard'])->name('dashboard');
        Route::get('/products/search', [WasteController::class, 'searchProducts'])->name('products.search');
        Route::get('/{id}',         [WasteController::class, 'show'])->name('show');
        Route::put('/{id}',         [WasteController::class, 'update'])->name('update');
        Route::delete('/{id}',      [WasteController::class, 'destroy'])->name('destroy');
    });
});
