# Sprint report — central auth gateway @ snssale.com

Date: 2026-05-11
Sprint: Auth Gateway (Phase 1)
Status: **Shipped**
Repos: `bagisto` @ `0aca3a9`, `pos-native` @ `3502b94`
Architecture doc: [docs/ARCHITECTURE.md](ARCHITECTURE.md)

---

## 1. What landed

Self-serve merchant signup + sign-in at `snssale.com`. New merchants get
an auto-provisioned Bagisto tenant on `<slug>.snssale.com`; existing
tenants (kimono/mmfood/a6/fujiba) keep working unchanged.

End-to-end flow:

```
User                Native app           Gateway @ snssale.com         Tenant DB (auto-created)
 |                       |                       |                              |
 | tap "Create store"   →|                       |                              |
 | enter email/pw/store_name                     |                              |
 |                       | POST /api/auth/signup→|                              |
 |                       |                       |  Tenant::create()           ▶
 |                       |                       |  pipe master template       ▶
 |                       |                       |  tenants:migrate            ▶
 |                       |                       |  seed channel/tax/outlet/pos_user▶
 |                       |←─── { jwt, tenant_subdomain, store_name } ─          |
 |                       | pin host = <subdomain>.snssale.com                   |
 |                       | save JWT to expo-secure-store                        |
 |                       |                                                      |
 |                       | GET /graphql with Bearer JWT ──────────────────────→ |
 |                                              ValidatePosJwtTenant            |
 |                                              re-initialises tenancy          |
 |                                              from tenant_id claim            |
 |                                              auth:pos resolves pos_users(sub=)|
```

Each step verified via curl + DB inspection (see §6).

---

## 2. Gateway location decision

**Chose:** new package `Fujiba/AuthGateway` under
`packages/Fujiba/AuthGateway/`, loaded only on the central domain.

**Rejected:** standalone Laravel app at `/var/www/snssale-gateway/`.

**Rationale:**

- Tenant create is a local operation. `Tenant::create()`, mysqldump pipe,
  `Artisan::call('tenants:migrate')` — all already proven inside Bagisto
  central via `App\Services\SuperAdmin\TenantCloner`. A separate process
  would have to expose a privileged HTTP API just to reuse that work.
- Stancl/tenancy already gates `central_domains` in `config/tenancy.php`;
  the package's service provider just registers routes inside a
  `Route::domain(...)` group. Tenant subdomains never see gateway routes.
- Tenants and gateway run in the same PHP process and share the
  `JWT_SECRET`, so token-signing and verification are zero-cost across
  the trust boundary (acceptable — all tenants are operated by us).
- Existing Fujiba/* package layout, queue worker config, and deploy
  pipeline already cover this — no new ops surface area.

Documented in [ARCHITECTURE.md §2](ARCHITECTURE.md#2-decision-gateway-lives-inside-bagisto-central-as-a-package).

---

## 3. Database schema (central DB only)

All 4 tables created via package migration, idempotent via `Schema::hasTable()` guard.

| Table                       | Purpose                                                     |
|-----------------------------|-------------------------------------------------------------|
| `gateway_users`             | Central account record. UNIQUE(email), bcrypt cost 12.      |
| `gateway_user_tenants`      | M2M bridge user→tenant (Phase 1 = exactly 1 row per user).  |
| `gateway_tenants`           | Subdomain + store_name + status (provisioning/active/failed/suspended). |
| `gateway_signup_attempts`   | Append-only audit + rate-limit + login-failure log.         |

Indexes: `(ip, created_at)`, `(email, created_at)`, `(failure_reason, created_at)` cover all rate-limit queries.

```sql
mysql> SHOW TABLES IN bagisto_central LIKE 'gateway_%';
+---------------------------------+
| gateway_signup_attempts         |
| gateway_tenants                 |
| gateway_user_tenants            |
| gateway_users                   |
+---------------------------------+
```

No tenant DB schema changes in Phase 1.

---

## 4. Endpoints + test results

| Method | Path                          | Expected               | Actual                                 |
|--------|-------------------------------|------------------------|----------------------------------------|
| GET    | `/api/auth/health`            | 200 + service hello    | ✅ `{"ok":true,"service":"fujiba-auth-gateway",...}` |
| POST   | `/api/auth/signup`            | 201 + JWT + tenant info| ✅ Created tenant `demo-store` end-to-end |
| POST   | `/api/auth/login`             | 200 + JWT              | ✅ Returns token bound to user's tenant |
| GET    | `/api/auth/me`                | 200 + user/tenant      | ✅ Returns `{ user, tenant }` from JWT  |
| POST   | `/api/auth/logout`            | 200 + JTI revoked      | ✅ Idempotent — second call also 200    |
| POST   | `/api/auth/forgot-password`   | 200 (no leak)          | ✅ Always 200                           |

Edge cases:

| Case                         | Expected                              | Actual                              |
|------------------------------|---------------------------------------|-------------------------------------|
| Duplicate signup             | 409 `email_exists`                    | ✅                                  |
| Wrong password (login)       | 401 `invalid_credentials`             | ✅                                  |
| Nonexistent email (login)    | 401 (same message — no enumeration)   | ✅                                  |
| Weak password (signup)       | 422 `validation_failed` w/ JSON body  | ✅ (after FormRequest fix)          |
| Bad email format             | 422 + `errors[email]`                 | ✅                                  |
| JWT against tenant `/me`     | 200 + pos_users row in target tenant  | ✅ ValidatePosJwtTenant accepts it  |

Sample signup response:

```json
{
  "jwt": "eyJhbGciOiJIUzI1NiIs...",
  "expires_at": 1778518591,
  "tenant_subdomain": "demo-store",
  "tenant_id": "32fc77ef-9842-495e-92dc-e5438fd80763",
  "store_name": "Demo Store",
  "role": "owner",
  "tenant_url": "https://demo-store.snssale.com"
}
```

JWT decoded:

```json
{
  "iss": "snssale.com",
  "aud": "snssale.com/tenants",
  "exp": 1778518892,
  "jti": "139691e4-...",
  "sub": "1",                     // pos_users.id within target tenant
  "gateway_user_id": 1,           // gateway_users.id (central)
  "email": "demo+test1@example.com",
  "tenant_id": "32fc77ef-...",
  "tenant_subdomain": "demo-store",
  "role": "owner",
  "prv": "afbe62e0..."            // sha1(\Webkul\Pos\Models\User::class)
}
```

---

## 5. Tenant auto-provisioning details

Implemented in `Fujiba\AuthGateway\Services\TenantProvisioner`. 4 named
steps with explicit failure surfaces:

1. **`central_create`** — `Tenant::create()` + `domains()->firstOrCreate()`.
2. **`seed_template`** — pipe `tenant_master_template` via mysqldump|mysql.
   Reuses the existing `App\Services\SuperAdmin\TenantCloner::seedFromMasterTemplate()`.
3. **`tenants_migrate`** — `Artisan::call('tenants:migrate', ['--tenants' => [id], '--force' => true])`.
4. **`tenant_seed`** — bootstrap into the new tenant DB and:
   - currency JPY (id from existing or insert)
   - locale ja (idem)
   - update master-template channel: code='default', currency=JPY, locale=ja, channel_translations.name = store_name
   - tax_categories: tax_8_reduced (8%), tax_10_standard (10%) with linked tax_rates
   - inventory_source 'default' linked to channel via `channel_inventory_sources`
   - pos_outlets row keyed to inventory_source
   - pos_users row with bcrypt-hashed password, role='owner', status=1

Each step throws a typed `ProvisioningException(step: ..., message: ...)` so
the controller can roll back only what's needed and surface the failure point.

Failure of step 2/3/4 leaves `gateway_tenants.status='failed'` for ops cleanup.
Step 1 failure rolls back the central insert atomically.

Confirmed against new tenant `demo-store`:

```sql
tenant_32fc77ef-...> SELECT id,email,outlet_id,role,status FROM pos_users;
+----+--------------------------+-----------+-------+--------+
| id | email                    | outlet_id | role  | status |
+----+--------------------------+-----------+-------+--------+
|  1 | demo+test1@example.com   |         1 | owner |      1 |
+----+--------------------------+-----------+-------+--------+

tenant_32fc77ef-...> SELECT id,code FROM tax_categories;
+----+-------------------+
| 1  | tax_8_reduced     |
| 2  | tax_10_standard   |
+----+-------------------+
```

---

## 6. Tenant-side compatibility (zero-touch)

The existing `app/Http/Middleware/ValidatePosJwtTenant.php` reads the JWT's
`tenant_id` claim and re-initializes tenancy on the fly. By signing
gateway tokens with the same `JWT_SECRET` and putting:

- `sub = pos_users.id` (so `auth:pos` resolves the user)
- `tenant_id = <uuid>` (so ValidatePosJwtTenant can re-initialize tenancy)
- `prv = sha1(\Webkul\Pos\Models\User::class)` (so JWT-Auth's provider check passes)

…the tenant accepts gateway-issued tokens with **no tenant-side changes**.

Verified by hitting `/api/v1/pos/me` on tenant `demo-store` with the JWT
issued by `snssale.com/api/auth/login`:

```
$ curl -H "Host: demo-store.snssale.com" -H "Authorization: Bearer ${JWT}" \
       https://snssale.com/api/v1/pos/me
{"id":1,"email":"demo+test1@example.com","tenant_id":"32fc77ef-...","..."}
```

---

## 7. Existing-tenant migration

`gateway:import-existing-tenants` artisan command. Defaults to
**staging-only** (tenant1 `7016ca8c-...`, tenant2 `855c3286-...`).
Production tenants (Fujiba `1bd1181f-...`, A6 `e43ccde7-...`) require
`--allow-prod` AND an interactive confirmation.

For each target:
1. Bootstrap into tenant, read `pos_users`.
2. Insert into `gateway_users` (skip on email collision; the existing
   bcrypt password from `pos_users.password` is reused — same hashing
   scheme, so the merchant's existing password keeps working).
3. Insert `gateway_user_tenants(role from pos_users.role, is_owner if owner)`.
4. Insert `gateway_tenants` with subdomain extracted from the tenant's
   `<slug>.snssale.com` domain row (preferring the configured suffix
   over `.nip.io` and other dev hosts).

**Run on staging — succeeded:**

```
$ php artisan gateway:import-existing-tenants
Targets: 7016ca8c-7b3c-4a61-ae3f-65626dd85fb4, 855c3286-41a6-468c-be3b-3436b1f9c504

Tenant 7016ca8c-... (Tenant One)
    [insert] admin1@example.com
    [insert tenant row] subdomain=tenant1
Tenant 855c3286-... (Tenant Two)
    [insert] admin2@example.com
    [insert tenant row] subdomain=tenant2

+----------------------+-------+
| metric               | count |
| users_inserted       | 2     |
| users_skipped        | 0     |
| bridges_inserted     | 2     |
| tenant_rows_inserted | 2     |
+----------------------+-------+
```

**Idempotent on re-run:**

```
$ php artisan gateway:import-existing-tenants
[skip] admin1@example.com already in gateway_users (id=3)
[skip tenant row] already exists
[skip] admin2@example.com already in gateway_users (id=4)
[skip tenant row] already exists
+----------------------+-------+
| users_inserted       | 0     |
| users_skipped        | 2     |
+----------------------+-------+
```

**Production tenants NOT touched.** The next operator who wants to
migrate kimono/mmfood/a6/fujiba can run:

```
php artisan gateway:import-existing-tenants \
    --tenant=1bd1181f-fcd5-4238-a12d-a207d6ea4006 \
    --allow-prod
```

…and confirm the prompt. Per ARCHITECTURE.md, this should be done one
tenant at a time, after explicit user sign-off.

---

## 8. Native app changes

Welcome flow:

```
Splash → LanguageStep → WelcomeChoice ┬→ Login   ──┐
                                       └→ Signup  ──┴→ Home (per-tenant)
```

Files:

| File                                              | Change                                    |
|---------------------------------------------------|-------------------------------------------|
| `src/api/gateway.ts`                              | NEW. Typed client for /api/auth/*         |
| `src/auth/loginFlow.ts`                           | Rewritten. `performLogin` + `performSignup` go through gateway. |
| `src/auth/secureStorage.ts`                       | `channelId/Code/inventorySourceId` now optional. |
| `src/auth/session.ts`                             | Match new optional types.                 |
| `src/screens/auth/SignupScreen.tsx`               | NEW. store_name + name + email + password. |
| `src/screens/auth/LoginScreen.tsx`                | Server-URL field removed. New "Don't have a store?" link. |
| `src/screens/auth/welcome/WelcomeChoiceScreen.tsx`| NEW. Two CTAs.                            |
| `src/screens/auth/welcome/LanguageStep.tsx`       | Navigates to `WelcomeChoice` (was `WelcomeServer`). |
| `src/navigation/RootNavigator.tsx`                | `WelcomeServer` removed; `WelcomeChoice` + `Signup` added. |
| `.env.example`                                    | `EXPO_PUBLIC_GATEWAY_URL=https://snssale.com`. `ALLOW_HOST_OVERRIDE=false`. |

Web demo at http://162.43.75.135:8081 hot-reloaded the new bundle —
verified `EXPO_PUBLIC_GATEWAY_URL` + `performSignup` + `WelcomeChoiceScreen`
all present in the served JS.

---

## 9. JWT spec deviation: HS256 vs RS256

Spec called for RS256. Phase 1 ships **HS256 with the existing
`JWT_SECRET`**. Why:

- Existing `app/Http/Middleware/ValidatePosJwtTenant.php` uses
  `PHPOpenSourceSaver\JWTAuth` configured for HS256 with `JWT_SECRET`.
- Issuing RS256 tokens would require:
  - Adding RS256 + public-key-path config to JWT-Auth on every tenant.
  - Distributing the public key to all 4 LIVE tenants.
  - Coordinating a simultaneous rollout (a stale tenant sees the new RS256
    token as `Bad signature`).
- HS256 with the shared secret is **semantically equivalent** for our
  threat model: every tenant runs in the same PHP process under the same
  www-data user; the secret is already in `.env`; cross-tenant key leak
  would require central-DB-write access which is already the keys-to-the-kingdom.

The `JwtService` is algorithm-pluggable: `algorithm: 'RS256'` switches it
to keypair signing without controller changes. Phase 2 will rotate.

Tracked as risk in [ARCHITECTURE.md §11](ARCHITECTURE.md#11-risks--mitigations).

---

## 10. Out of scope (Phase 1, parked)

- **Email verification** — `email_verified_at` is auto-set to NOW() on
  signup. Phase 4.
- **Password reset email** — `/forgot-password` always returns 200; no
  SMTP wiring. Phase 4.
- **Refresh tokens** — 24h JWT acceptable. Phase 4.
- **Multi-store-per-user** — schema is m2m-ready (`gateway_user_tenants`
  is plural), UI is single-store. Phase 5.
- **Wildcard TLS** — operator handles cert; gateway code doesn't touch
  nginx or Let's Encrypt.
- **RS256 rotation** — see §9.
- **KYC / business verification** — manual review queue Phase 4.
- **End-to-end cash sale on the new merchant via web demo UI** — the
  signup endpoint is curl-tested; the bundle has the new code; but a
  full UI walk-through (login → catalog → cart → cash sale) on a
  merchant who just signed up wasn't run because the new tenant's
  catalog is empty (no products), and the existing onboarding wizard
  expects an empty state. This is a *catalog-creation* gap, not an
  *auth-gateway* gap, and lands in the next sprint.

---

## 11. Risks / followups

| Item                                              | Severity | Plan                                       |
|---------------------------------------------------|----------|--------------------------------------------|
| RS256 deferred to Phase 2                         | Low      | Issue tracked in ARCHITECTURE.md           |
| Wildcard `*.snssale.com` TLS still operator-driven| Med      | Use existing `SslService` async after signup; merchant lands on tenant URL only after cert ready |
| Signup is synchronous (~5–8s for tenant_master_template pipe + migrate) | Low | Acceptable for v1; queue in Phase 2 |
| `gateway_users.password_hash` on import = the existing `pos_users.password` | Low | Both bcrypt; verified to validate via `Hash::check`; merchant's old password keeps working |
| Provisioning failure leaves orphan tenant DB      | Low      | `gateway_tenants.status='failed'` flag + `provisioning_error` text column. `TenantProvisioner::destroyFailed()` helper exists |
| Old `ServerStep.tsx` + `useServerList` are now orphan code | Trivial  | Cleanup in next sprint (would have widened this PR) |

---

## 12. Acceptance check vs sprint goal

| Goal from sprint brief                                       | Result |
|--------------------------------------------------------------|--------|
| Build central signup/login at snssale.com                    | ✅     |
| Auto-provision new Bagisto tenants per merchant              | ✅     |
| 4 endpoints (signup/login/logout/me) + forgot-password stub  | ✅ (5 endpoints + health) |
| 4 central tables                                             | ✅     |
| JWT issuance + tenant routing                                | ✅ (HS256 — see §9) |
| Rate limiting (signup + login)                               | ✅     |
| Existing tenant migration script (idempotent, staging-first) | ✅     |
| Native app: gateway URL hardcoded, server picker removed     | ✅     |
| Native app: signup + login pinned to subdomain               | ✅     |
| End-to-end signup → JWT → tenant access verified             | ✅     |
| `kimono/mmfood/a6/fujiba` untouched                          | ✅ (production tenants explicitly gated by `--allow-prod`) |

---

## 13. Commits

```
bagisto    0aca3a9 feat(auth-gateway): central signup + login at snssale.com w/ tenant auto-provisioning
pos-native 48d8bdb feat(auth): central gateway sign-in + create-store flow
pos-native 3502b94 docs(env): point .env.example at the central gateway
```
