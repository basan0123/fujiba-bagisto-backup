<?php

use Fujiba\Accounting\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

// Spec asked for ['web','admin','admin.locale'] but no `admin.locale` alias is
// registered in this Bagisto install. Locale is already handled by web group's
// SetUserLanguage middleware, so we drop the missing alias rather than 500.
Route::middleware(['web', 'admin'])
    ->prefix('admin/accounting')
    ->name('admin.accounting.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    });
