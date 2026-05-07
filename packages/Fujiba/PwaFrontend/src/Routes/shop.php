<?php

use Fujiba\PwaFrontend\Http\Controllers\Shop\MessengerAutoLoginController;
use Fujiba\PwaFrontend\Http\Controllers\Shop\RobotsController;
use Fujiba\PwaFrontend\Http\Controllers\Shop\SitemapController;
use Illuminate\Support\Facades\Route;

/**
 * PWA-side, session-bearing routes. Distinct from the existing storefront
 * Blade routes in `routes/web.php` — those stay untouched.
 */
Route::middleware(['web'])->group(function () {
    Route::get('pwa/messenger/autologin', MessengerAutoLoginController::class)
        ->name('pwa.messenger.autologin');

    Route::get('sitemap.xml', SitemapController::class)
        ->name('fujiba.sitemap');

    Route::get('robots.txt', RobotsController::class)
        ->name('fujiba.robots');
});
