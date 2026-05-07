<?php

use Fujiba\InvoicesAdmin\Http\Controllers\InvoiceSummaryController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'admin', 'auth:admin'],
    'prefix'     => config('app.admin_url') . '/fujiba-invoices',
], function () {
    Route::get('summary',        [InvoiceSummaryController::class, 'summary'])->name('fujiba.invoices.summary');
    Route::get('chat-metadata',  [InvoiceSummaryController::class, 'chatMetadata'])->name('fujiba.invoices.chat_metadata');
});
