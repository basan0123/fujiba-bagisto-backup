<?php

use Fujiba\AutoLogin\Http\Controllers\Admin\QrLoginGeneratorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'admin', 'auth:admin'])
    ->prefix(config('app.admin_url') . '/fujiba/autologin')
    ->name('admin.fujiba.autologin.')
    ->group(function () {
        Route::get('qr-generator', [QrLoginGeneratorController::class, 'index'])
            ->name('qr_generator');
    });
