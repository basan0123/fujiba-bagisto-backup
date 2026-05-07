<?php

use Fujiba\AutoLogin\Http\Controllers\TikTokLoginController;
use Illuminate\Support\Facades\Route;

// TikTok customer SSO into the storefront.
// Shape mirrors the existing Messenger routes in /routes/web.php.
Route::middleware(['web'])->group(function () {
    Route::get('/tiktok/login',     [TikTokLoginController::class, 'login'])->name('autologin.tiktok.login');
    Route::get('/tiktok/autologin', [TikTokLoginController::class, 'login'])->name('autologin.tiktok.autologin');
});
