<?php

use Fujiba\Accounting\Http\Controllers\Admin\DashboardController;
use Fujiba\Accounting\Http\Controllers\Admin\InvoiceRegistrantApiController;
use Fujiba\Accounting\Http\Controllers\Admin\JournalController;
use Fujiba\Accounting\Http\Controllers\Admin\ReceiptController;
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

        // Invoice-registrant lookup JSON API
        Route::get('/api/invoice-registrants/{t_number}', [InvoiceRegistrantApiController::class, 'show'])
            ->where('t_number', 'T\d{13}|.*')   // permissive — controller validates and returns 422
            ->name('api.invoice-registrants.show');
    });
