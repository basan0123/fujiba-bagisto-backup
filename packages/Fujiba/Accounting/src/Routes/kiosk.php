<?php

use Fujiba\Accounting\Http\Controllers\TimecardKioskController;
use Illuminate\Support\Facades\Route;

// Public kiosk routes — no admin auth. Token-based identity: a staff member's
// QR token is the auth secret. Mount under /accounting/kiosk so it's separate
// from /admin/* routes.
Route::middleware(['web'])
    ->prefix('accounting/kiosk')
    ->name('accounting.kiosk.')
    ->group(function () {
        Route::get('/',         [TimecardKioskController::class, 'index'])->name('index');
        Route::post('/punch',   [TimecardKioskController::class, 'punch'])->name('punch');
    });
