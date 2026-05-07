<?php

use Fujiba\TikTokShop\Http\Controllers\Webhook\TikTokWebhookController;
use Illuminate\Support\Facades\Route;

// No auth middleware — verified by HMAC after tenant lookup.
// Rate-limited per IP to defend against unauth flood (master prompt §4 + §9).
Route::post('webhook/tiktok/{tenantId}', [TikTokWebhookController::class, 'handle'])
    ->name('webhook.tiktok')
    ->middleware(['api', 'throttle:60,1']);
