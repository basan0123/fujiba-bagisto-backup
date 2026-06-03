<?php

use App\Http\Middleware\ValidatePosJwtTenant;
use Illuminate\Support\Facades\Route;
use Webkul\Pos\Http\Controllers\CustomerDisplayController;
use Webkul\Pos\Http\Controllers\CustomerDisplayPublicController;

/**
 * Customer Display — JWT-guarded API for the cashier terminal.
 *
 * ValidatePosJwtTenant reads the JWT's tenant_id claim and re-initializes
 * tenancy from it. Without this, a JWT issued for tenant A but presented
 * to host serving tenant B would write rows against the wrong tenant DB.
 * The global InitializeTenancyForSubdomains resolves by Host only.
 *
 * ORDER MATTERS: ValidatePosJwtTenant MUST run before auth:pos. On a host
 * with no Domain row (e.g. pos.snssale.com) the global subdomain middleware
 * leaves tenancy on the empty central DB; if auth:pos ran first it would look
 * up the agent (JWT sub) against central pos_users, not find it, and 401 before
 * the JWT's tenant_id ever got a chance to initialize the right tenant DB.
 * Running ValidatePosJwtTenant first switches to the JWT's tenant (and resolves
 * + pins the agent via resolvePosAgent), so auth:pos then sees an authed agent.
 * This mirrors the proven GraphQL stack (config/lighthouse.php), where
 * ValidatePosJwtTenant precedes any field-level @guard(pos).
 */
Route::group(['middleware' => [ValidatePosJwtTenant::class, 'auth:pos'], 'prefix' => 'pos/api/customer-display'], function () {
    Route::post('pair',                 [CustomerDisplayController::class, 'pair']);
    Route::get('sessions',              [CustomerDisplayController::class, 'index']);
    Route::post('sessions/{id}/heartbeat', [CustomerDisplayController::class, 'heartbeat'])
        ->whereNumber('id');
    Route::delete('sessions/{id}',      [CustomerDisplayController::class, 'revoke'])
        ->whereNumber('id');
});

/**
 * Customer Display — public, no auth. Token IS the credential.
 * Tenancy is resolved by the project's domain middleware in the global stack;
 * the stream handler also asserts row.tenant_id === tenant()->id.
 */
Route::group(['prefix' => 'pos/display'], function () {
    Route::get('{token}',         [CustomerDisplayPublicController::class, 'show'])
        ->where('token', '[A-Za-z0-9_-]{32,43}');
    Route::get('{token}/stream',  [CustomerDisplayPublicController::class, 'stream'])
        ->where('token', '[A-Za-z0-9_-]{32,43}');
    Route::post('{token}/ping',   [CustomerDisplayPublicController::class, 'ping'])
        ->where('token', '[A-Za-z0-9_-]{32,43}');
});

Route::post('pos/api/customer-display/beacon-revoke',
    [CustomerDisplayPublicController::class, 'beaconRevoke']);

/**
 * FUJIBA 2026-05-30: removed the public storefront chat endpoints
 * (pos/chat/{start,send,messages} → ShopChatController). They backed the
 * now-deleted storefront chat widget and its pos_chat_* tables. POS chat is
 * Messenger-only and served via the POS GraphQL chat resolvers, which proxy the
 * existing Fujiba\LiveChat / Chatpion Facebook Messenger stack.
 *
 * Catch-all that serves the POS Vue app. MUST come last — specific routes above
 * are registered first and win on exact match.
 */
Route::group(['middleware' => ['shop', 'pos'], 'prefix' => 'pos'], function () {
    Route::view('{any?}', 'pos::shop.index')->where('any', '.*');
});
