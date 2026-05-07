<?php

// routes/api.php

use App\Http\Controllers\Api\AutologinController;
use App\Http\Controllers\Api\V1\POS\AgentHardwareSettingController;
use App\Http\Controllers\Api\V1\POS\AuthController;
use App\Http\Controllers\Api\V1\POS\BarcodeResolveController;
use App\Http\Controllers\Api\V1\POS\PrintReceiptController;
use App\Http\Controllers\Api\V1\POS\ReceiptSettingsController;
use App\Http\Controllers\Api\V1\POS\StocktakeController;
use App\Http\Controllers\Api\V1\POS\SystemController;
use App\Http\Middleware\ValidatePosJwtTenant;
use Illuminate\Support\Facades\Route;

Route::post('autologin', AutologinController::class)
    ->middleware(['throttle:30,1'])
    ->name('api.autologin');

Route::prefix('v1/pos')->name('pos.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:pos-login')
        ->name('login');

    Route::get('me', [AuthController::class, 'me'])
        ->middleware(['auth:pos', 'throttle:60,1'])
        ->name('me');

    Route::middleware(['auth:pos', ValidatePosJwtTenant::class])->group(function () {
        Route::get('agent/settings',  [AgentHardwareSettingController::class, 'show']);
        Route::post('agent/settings', [AgentHardwareSettingController::class, 'update']);
        Route::get('system/printers', [SystemController::class, 'printers']);
        Route::post('print-receipt',      [PrintReceiptController::class, 'print']);
        Route::post('print-test-receipt', [PrintReceiptController::class, 'testPrint']);

        Route::get('receipt-settings',  [ReceiptSettingsController::class, 'show']);
        Route::post('receipt-settings', [ReceiptSettingsController::class, 'update']);

        Route::get('resolve-barcode', [BarcodeResolveController::class, 'resolve']);

        Route::get('stocktake-sessions',         [StocktakeController::class, 'index']);
        Route::get('stocktake-sessions/{id}',    [StocktakeController::class, 'show'])->whereNumber('id');
        Route::post('stocktake-sessions',        [StocktakeController::class, 'store']);
    });
});
