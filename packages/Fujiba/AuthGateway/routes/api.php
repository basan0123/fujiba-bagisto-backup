<?php

use Fujiba\AuthGateway\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Gateway routes
|--------------------------------------------------------------------------
|
| All routes are scoped to the `gateway_domains` set in
| config/auth-gateway.php — registered only when the request hits one of
| those hostnames so tenant subdomains never see them.
|
*/

Route::prefix('api/auth')
    ->middleware(['api'])
    ->group(function () {
        Route::post('signup',          [AuthController::class, 'signup']);
        Route::post('login',           [AuthController::class, 'login']);
        Route::post('logout',          [AuthController::class, 'logout']);
        Route::get('me',               [AuthController::class, 'me']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);

        // Lightweight health probe so monitors can hit one URL without auth.
        Route::get('health', function () {
            return response()->json([
                'ok'      => true,
                'service' => 'fujiba-auth-gateway',
                'time'    => now()->toIso8601String(),
            ]);
        });
    });
