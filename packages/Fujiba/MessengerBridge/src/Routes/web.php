<?php

use Fujiba\MessengerBridge\Http\Controllers\MessengerBridgeController;
use Illuminate\Support\Facades\Route;

/*
 * Bridge entry point. The 'web' middleware is required so flash messages
 * survive the redirect to channel home on failure. CSRF is moot — this is
 * a GET, and the cryptographic gate is the HMAC token, not session.
 */
Route::middleware('web')
    ->get('/messenger-bridge', MessengerBridgeController::class)
    ->name('messenger.bridge');
