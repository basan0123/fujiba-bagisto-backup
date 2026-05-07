<?php

use Fujiba\LiveChat\Http\Controllers\Shop\MessengerLoginController;

Route::group(['prefix' => 'messenger', 'middleware' => 'web', 'as' => 'messenger.'], function () {
    Route::get('/login',  [MessengerLoginController::class, 'login'])->name('login');
    Route::get('/status', [MessengerLoginController::class, 'status'])->name('status');
});
