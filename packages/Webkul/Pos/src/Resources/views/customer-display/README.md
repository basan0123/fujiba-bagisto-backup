# POS Customer Display

Per-cashier customer-facing screen. Customer's phone scans the QR shown on the
cashier's settings page; phone opens an SSE stream that receives canonical
totals computed server-side from the cart. One pairing token per terminal
session; tokens are the credential.

## Session lifetime

**Sessions never expire.** `pair()` sets `expires_at` 100 years in the
future, and all lookups (`pair`, `index`, `heartbeat`, `resolveActive`,
`stream`) ignore `expires_at`. A session lives until one of:

- Cashier manually revokes it (Settings → Display → Revoke button)
- Agent logs out (`RevokeCustomerDisplaysOnLogout` listener)
- Cashier tab closes (pagehide beacon → `beacon-revoke`)
- Row is deleted directly

**Risk accepted by user:** the pairing token is a forever credential
until revoked. Anyone who captures the URL (browser history, screenshot,
stranger glancing at a customer's phone) can connect to the SSE stream
indefinitely. Mitigation if this becomes a problem: lower `addYears(100)`
in `CustomerDisplayController::pair` to a real TTL (e.g. `addDays(30)`)
and re-enable the existing `pos:customer-displays:revoke-expired` sweeper.

The column is intentionally kept on the table so TTL can be re-introduced
without a schema change.

## Operational thresholds

**>5 concurrent displays per server → split php-fpm pool.**
SSE streams hold an fpm worker for the life of the connection. The default
pool (`/etc/php/8.4/fpm/pool.d/www.conf`) serves all PHP requests. With more
than ~5 concurrent customer displays, regular request workers can starve.
Mitigation: dedicated pool with its own listen socket for `/pos/display/*/stream`
location in nginx. Not required for 1–3 register shops.

**fpm `request_terminate_timeout` must be ≥ `MAX_STREAM_SECONDS` + buffer.**
`MAX_STREAM_SECONDS` is 1800 (30 min) in `CustomerDisplayPublicController`.
Set fpm to ≥ 1860, or leave at default 0 (PHP `max_execution_time` controls,
and the controller calls `set_time_limit(0)` defensively).

## Nginx (production)

Add **before** the generic `location ~ \.php$` block in
`/etc/nginx/sites-enabled/bagisto`:

```nginx
location ~ ^/pos/display/[^/]+/stream$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_read_timeout 1860;
    fastcgi_buffering off;
    fastcgi_keep_conn on;
    proxy_buffering off;
    add_header X-Accel-Buffering no;
    gzip off;
}
```

If a load balancer sits in front (Cloudflare, AWS ALB), its idle timeout must
also be ≥ 1860s, or the stream silently drops at the LB's value (often 60s).

## Channel filtering caveat

`core()->getCurrentChannel()` is called from a singleton resolver that reads
cached config. The `channels` table lives in the **central** DB only — see
`feedback_repo_cache_tenant_bleed.md`. Inside POS context the channel may
default unexpectedly. Slice 1's heartbeat does **not** filter the cart by
channel for this reason; ownership is enforced by `agent_id`. If slice 3's
discovery list needs per-channel scoping, join through `pos_outlets.channel_id`
on the agent's outlet rather than calling `core()->getCurrentChannel()` from
within tenant context.

## Diagnostic queries

When a session drops and you need to know why:

```php
// tinker --tenant=<id>
\Webkul\Pos\Models\CustomerDisplaySession::where('agent_id', $agentId)
    ->whereNotNull('revoked_at')
    ->orderByDesc('revoked_at')
    ->limit(20)
    ->get(['id', 'paired_device_label', 'last_seen_at', 'revoked_at', 'revoked_reason']);
```

`revoked_reason` values: `manual` (cashier hit revoke), `logout` (agent logged
out), `beacon` (terminal tab closed), `expired` (sweeper).

Live sessions for an agent:

```php
\Webkul\Pos\Models\CustomerDisplaySession::where('agent_id', $agentId)
    ->whereNull('revoked_at')
    ->where('expires_at', '>', now())
    ->get(['id', 'paired_device_label', 'last_seen_at', 'last_payload_at', 'expires_at']);
```

## Hardware test protocol

Run all five on real iPad + iPhone on the target Wi-Fi. Capture a one-line
result per test.

1. **30-min idle.** Pair, leave the customer page open and untouched on the
   iPhone for 30 minutes. Watch for the `event: rotate` and clean reload at
   the rotation boundary. Catches proxy timeouts, fpm timeouts, iOS background
   throttling.
2. **Wi-Fi blip.** Toggle the customer phone's Wi-Fi off for 5 seconds, on.
   EventSource should reconnect, the `Last-Event-ID` header should be sent
   on the reconnect, and the page should immediately resync to current totals.
3. **Tab-switch 60s.** Switch to another app on the customer iPhone for 60s,
   come back. The page should either still be live or detect dead connection
   via the visibility-change handler and reload.
4. **Two displays at once.** Two POS terminals each pair their own customer
   phone. Cashier A's heartbeat must not appear on Cashier B's customer phone.
   Tests token isolation.
5. **fpm worker check.** While streams are active, run
   `ps aux | grep php-fpm | wc -l` on the server. Each connected display ties
   up one worker. Browse the storefront in a third tab — it should still
   respond. Catches worker starvation.

## Files

- Migration: `packages/Webkul/Pos/src/Database/Migrations/2026_05_01_000001_create_pos_customer_display_sessions_table.php`
- Model: `packages/Webkul/Pos/src/Models/CustomerDisplaySession.php`
- Controllers: `packages/Webkul/Pos/src/Http/Controllers/CustomerDisplay{,Public}Controller.php`
- View: `packages/Webkul/Pos/src/Resources/views/customer-display/show.blade.php`
- Listener: `packages/Webkul/Pos/src/Listeners/RevokeCustomerDisplaysOnLogout.php`
- Sweeper: `packages/Webkul/Pos/src/Console/Commands/RevokeExpiredCustomerDisplays.php`
- Beacon hook: `packages/Webkul/Pos/src/Resources/assets/js/{shop.js,composable/customer-display-tokens.js}`
- Routes: `packages/Webkul/Pos/src/Routes/shop-routes.php`
- Schedule: `bootstrap/app.php` (`tenants:run pos:customer-displays:revoke-expired`, every 15 min)
