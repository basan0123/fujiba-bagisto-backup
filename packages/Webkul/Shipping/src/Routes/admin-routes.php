<?php

use Illuminate\Support\Facades\Route;
use Webkul\Shipping\Http\Controllers\Admin\CoolFeeController;
use Webkul\Shipping\Http\Controllers\Admin\FreeShippingThresholdController;
use Webkul\Shipping\Http\Controllers\Admin\PrefectureShippingController;
use Webkul\Shipping\Http\Controllers\Admin\BulkBonusController;
use Webkul\Shipping\Http\Controllers\Admin\ProductExclusionController;
use Webkul\Shipping\Http\Controllers\Admin\ZoneShippingController;

Route::group([
    'middleware' => ['web', 'admin'],
    'prefix'     => config('app.admin_url'),
], function () {

    Route::prefix('shipping')->name('admin.shipping.')->group(function () {

        Route::resource('cool-fees',        CoolFeeController::class);
        Route::resource('thresholds',       FreeShippingThresholdController::class);
        Route::resource('prefecture-rates', PrefectureShippingController::class);
        Route::post('prefecture-rates/import', [PrefectureShippingController::class, 'import'])
             ->name('prefecture-rates.import');
        Route::get('prefecture-rates/export',  [PrefectureShippingController::class, 'export'])
             ->name('prefecture-rates.export');
        Route::resource('bulk-bonus',  BulkBonusController::class);
        Route::resource('exclusions',  ProductExclusionController::class);

        // Zone-based shipping management
        Route::post('zones/seed',   [ZoneShippingController::class, 'seed'])->name('zones.seed');
        Route::resource('zones',    ZoneShippingController::class);

    });

});
