<?php

return [
    /*
     * The hosts that the gateway considers itself responsible for. Anything
     * else is treated as a tenant subdomain and the gateway routes are not
     * registered. Keep this in sync with config/tenancy.php#central_domains.
     */
    'gateway_domains' => [
        'snssale.com',
        'www.snssale.com',
        '162.43.75.135.nip.io',
        '162.43.75.135',
        'localhost',
        '127.0.0.1',
    ],

    /*
     * Tenant subdomain suffix. Tenant 'kimono' lives at kimono.<suffix>.
     * Override per-environment if you want staging on a different domain.
     */
    'tenant_subdomain_suffix' => env('GATEWAY_TENANT_SUFFIX', 'snssale.com'),

    /*
     * Subdomains that may NOT be allocated to merchants — would clash with
     * platform infrastructure or look like an official endpoint.
     */
    'reserved_subdomains' => [
        'www', 'admin', 'api', 'mail', 'dev', 'staging', 'snssale', 'pos',
        'central', 'gateway', 'app', 'support', 'help', 'status',
        // current LIVE tenants:
        'kimono', 'mmfood', 'a6', 'fujiba',
    ],

    /*
     * JWT signing.
     *
     * Phase 1: HS256 with the existing JWT_SECRET so gateway-issued tokens
     * are accepted by the existing tenant-side ValidatePosJwtTenant middleware
     * with NO tenant-side changes. Tenants and gateway run in the same process
     * so the shared secret is not crossing a trust boundary.
     *
     * Phase 2: rotate to RS256 + per-tenant key distribution. Spec called for
     * RS256; we deferred because it would require simultaneous key rollout to
     * 4 LIVE tenants and a config publish per env (see ARCHITECTURE.md §5.3).
     */
    'jwt' => [
        'algorithm'   => env('GATEWAY_JWT_ALGO', 'HS256'),
        'secret'      => env('POS_JWT_SECRET') ?: env('JWT_SECRET'),
        'issuer'      => 'snssale.com',
        'audience'    => 'snssale.com/tenants',
        'kid'         => 'gateway-2026-05-11',
        'ttl_seconds' => (int) env('GATEWAY_JWT_TTL', 86400), // 24h
    ],

    /*
     * Rate limit knobs.
     */
    'rate_limits' => [
        'signup_per_ip_per_hour'   => 3,
        'signup_per_ip_per_day'    => 10,
        'login_fail_per_email_15m' => 5,
        'login_lockout_per_email'  => 10,
        'forgot_per_email_per_hour' => 3,
    ],

    /*
     * Provisioning seed defaults.
     */
    'seed' => [
        'currency_code' => 'JPY',
        'locale_code'   => 'ja',
        'tax_rates' => [
            // 8% reduced (food, takeout) and 10% standard (most goods).
            ['code' => 'tax_8_reduced',   'name' => 'Reduced 8%',   'rate' => 8.0],
            ['code' => 'tax_10_standard', 'name' => 'Standard 10%', 'rate' => 10.0],
        ],
    ],
];
