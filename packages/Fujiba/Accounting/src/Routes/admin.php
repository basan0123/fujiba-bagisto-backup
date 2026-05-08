<?php

use Fujiba\Accounting\Http\Controllers\Admin\DashboardController;
use Fujiba\Accounting\Http\Controllers\Admin\InvoiceRegistrantApiController;
use Fujiba\Accounting\Http\Controllers\Admin\JournalController;
use Fujiba\Accounting\Http\Controllers\Admin\PayrollController;
use Fujiba\Accounting\Http\Controllers\Admin\ReceiptController;
use Fujiba\Accounting\Http\Controllers\Admin\StaffController;
use Fujiba\Accounting\Http\Controllers\Admin\TimecardController;
use Illuminate\Support\Facades\Route;

// Spec asked for ['web','admin','admin.locale'] but no `admin.locale` alias is
// registered in this Bagisto install. Locale is already handled by web group's
// SetUserLanguage middleware, so we drop the missing alias rather than 500.
Route::middleware(['web', 'admin'])
    ->prefix('admin/accounting')
    ->name('admin.accounting.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

        Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
        Route::get('/journal/{id}', [JournalController::class, 'show'])
            ->whereNumber('id')
            ->name('journal.show');

        // Receipts
        Route::get('/receipts',                  [ReceiptController::class, 'index'])->name('receipts.index');
        Route::get('/receipts/create',           [ReceiptController::class, 'create'])->name('receipts.create');
        Route::post('/receipts',                 [ReceiptController::class, 'store'])->name('receipts.store');
        Route::get('/receipts/{id}',             [ReceiptController::class, 'show'])->whereNumber('id')->name('receipts.show');
        Route::put('/receipts/{id}',             [ReceiptController::class, 'update'])->whereNumber('id')->name('receipts.update');
        Route::post('/receipts/{id}/ocr',        [ReceiptController::class, 'runOcr'])->whereNumber('id')->name('receipts.ocr');
        Route::post('/receipts/{id}/confirm',    [ReceiptController::class, 'confirm'])->whereNumber('id')->name('receipts.confirm');
        Route::post('/receipts/{id}/reject',     [ReceiptController::class, 'reject'])->whereNumber('id')->name('receipts.reject');
        Route::delete('/receipts/{id}',          [ReceiptController::class, 'destroy'])->whereNumber('id')->name('receipts.destroy');
        Route::get('/receipts/{id}/file',        [ReceiptController::class, 'file'])->whereNumber('id')->name('receipts.file');

        // Staff
        Route::get('/staff',                     [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/create',              [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff',                    [StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{id}',                [StaffController::class, 'edit'])->whereNumber('id')->name('staff.edit');
        Route::put('/staff/{id}',                [StaffController::class, 'update'])->whereNumber('id')->name('staff.update');
        Route::delete('/staff/{id}',             [StaffController::class, 'destroy'])->whereNumber('id')->name('staff.destroy');
        Route::get('/staff/{id}/qr',             [StaffController::class, 'qrPrint'])->whereNumber('id')->name('staff.qr');

        // Timecards
        Route::get('/timecards',                 [TimecardController::class, 'index'])->name('timecards.index');
        Route::get('/timecards/{id}',            [TimecardController::class, 'edit'])->whereNumber('id')->name('timecards.edit');
        Route::put('/timecards/{id}',            [TimecardController::class, 'update'])->whereNumber('id')->name('timecards.update');
        Route::post('/timecards/{id}/approve',   [TimecardController::class, 'approve'])->whereNumber('id')->name('timecards.approve');

        // Payroll
        Route::get('/payroll',                   [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/run',              [PayrollController::class, 'run'])->name('payroll.run');
        Route::get('/payroll/{id}',              [PayrollController::class, 'show'])->whereNumber('id')->name('payroll.show');
        Route::post('/payroll/{id}/finalize',    [PayrollController::class, 'finalize'])->whereNumber('id')->name('payroll.finalize');

        // Invoice-registrant lookup JSON API
        Route::get('/api/invoice-registrants/{t_number}', [InvoiceRegistrantApiController::class, 'show'])
            ->where('t_number', 'T\d{13}|.*')
            ->name('api.invoice-registrants.show');
    });
