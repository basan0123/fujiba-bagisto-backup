<?php

use Fujiba\Notification\Http\Controllers\Admin\NotificationTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'admin', 'auth:admin'])
    ->prefix('admin/fujiba-notifications')
    ->name('admin.fujiba.notifications.')
    ->group(function () {
        Route::get('/',                  [NotificationTemplateController::class, 'index'])->name('index');
        Route::post('/',                 [NotificationTemplateController::class, 'save'])->name('save');
        Route::post('/{id}/toggle',      [NotificationTemplateController::class, 'toggle'])->name('toggle');
        Route::delete('/{id}',           [NotificationTemplateController::class, 'destroy'])->name('destroy');

        // Admin Alerts — recipient PSIDs per channel
        Route::post('/recipients',           [NotificationTemplateController::class, 'recipientsStore'])->name('recipients.store');
        Route::post('/recipients/{id}/toggle', [NotificationTemplateController::class, 'recipientsToggle'])->name('recipients.toggle');
        Route::delete('/recipients/{id}',    [NotificationTemplateController::class, 'recipientsDestroy'])->name('recipients.destroy');
    });
