<?php

use Fujiba\AdminSecurity\Http\Controllers\SecurityController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'admin', 'auth:admin'],
    'prefix' => config('app.admin_url') . '/security',
], function () {
    Route::get('settings', [SecurityController::class, 'index'])->name('admin.security.settings');
    Route::post('settings', [SecurityController::class, 'update'])->name('admin.security.settings.update');
    Route::post('settings/test', [SecurityController::class, 'test'])->name('admin.security.settings.test');
});
