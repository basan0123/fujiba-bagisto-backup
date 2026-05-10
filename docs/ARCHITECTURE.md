# Fujiba SaaS — Auth Gateway architecture

Date: 2026-05-11
Status: **Phase 1 design — implementation in progress**
Owner: Auth Gateway sprint
Related code: `packages/Fujiba/AuthGateway/`

---

## 1. Goal

Stand up `snssale.com` as a tenant-aware central auth gateway: any merchant
can sign up, get an auto-provisioned Bagisto tenant on a `*.snssale.com`
subdomain, and sign in transparently from the React Native POS app.

Without this, the 95%-complete native UI can't reach a real backend for
anyone other than the four hand-provisioned tenants (kimono, mmfood, a6,
fujiba).

---

## 2. Decision: gateway lives inside Bagisto central as a package

**Choice:** new package `Fujiba/AuthGateway` under
`packages/Fujiba/AuthGateway/`, loaded only on the central domain.

**Rejected alternative:** separate Laravel app at
`/var/www/snssale-gateway/`.

**Rationale:**

- **Tenant create is a local operation.** Provisioning a new tenant means
  `Tenant::create()` on the central DB, `Artisan::call('tenants:migrate')`,
  domain attach, and seed. All of this is already implemented in
  `App\Services\SuperAdmin\TenantCloner` + `App\Jobs\CloneTenantJob`. A
  separate process would have to expose a privileged HTTP API just to
  reuse that work — pure friction.
- **Single source of truth for the central DB.** Stancl/tenancy v3.9 puts
  the `tenants` and `domains` tables on the central connection. The
  gateway needs to read/write those plus four new `gateway_*` tables.
  Co-locating eliminates a cross-process distributed-write problem on
  signup (4-table central insert + tenant DB create + tenant migration +
  pos_user insert).
- **Domain-scoped loading.** Stancl/tenancy already knows which hostnames
  are central (`config/tenancy.php#central_domains` includes
  `snssale.com`). The gateway service provider gates route registration
  on the request host so tenant subdomains never see the gateway routes
  and never accidentally load gateway middleware. (See §6 for how.)
- **Existing patterns.** Every other Fujiba* package follows the same
  layout. Operations runbook, deploy pipeline, queue worker config, and
  monitoring already cover packages — adding a separate app means new
  ops surface area.
- **JWT verification on tenant side stays small.** Tenants already
  autoload the `Fujiba\AuthGateway` namespace (composer PSR-4 entry on
  the same Bagisto repo), so tenant-side middleware can `use` the
  shared `JwtVerifier` service without duplication.

**Trade-off accepted:** the gateway's failure mode is coupled to central
Bagisto. If central goes down, no one can log in. Mitigation: central
already has to be up for tenants to load anyway (DB-per-tenant manager
queries `tenants` on every request). So this isn't a new dependency.

---

## 3. Domain layout

| Domain                  | Role                                            |
|-------------------------|-------------------------------------------------|
| `snssale.com`           | Auth gateway routes (`/api/auth/*`) + landing   |
| `www.snssale.com`       | Same as `snssale.com` (alias)                   |
| `kimono.snssale.com`    | Existing tenant (LIVE) — untouched              |
| `mmfood.snssale.com`    | Existing tenant (LIVE) — untouched              |
| `a6.snssale.com`        | Existing tenant (LIVE) — untouched              |
| `fujiba.snssale.com`    | Existing tenant (LIVE) — untouched              |
| `*.snssale.com`         | New auto-provisioned tenants                    |

`config/tenancy.php#central_domains` already lists `snssale.com` +
`www.snssale.com`. No change needed there.

Wildcard TLS is **out of scope for Phase 1** — operator action. The
gateway code does not touch nginx or Let's Encrypt. `SslService`
(`app/Services/SuperAdmin/SslService.php`) handles per-tenant cert
issuance today; we'll reuse it on signup but won't gate signup
success on cert readiness (the merchant can hit the API immediately
even if the browser lands later when DNS+cert propagate).

---

## 4. Database schema (central DB only)

All 4 tables live in `bagisto_central`. **No tenant DB schema changes
in Phase 1.**

### 4.1 `gateway_users`

Central account record — one per merchant human.

```sql
CREATE TABLE gateway_users (
  id                   BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  email                VARCHAR(190) NOT NULL UNIQUE,
  password_hash        VARCHAR(255) NOT NULL,        -- bcrypt cost 12
  name                 VARCHAR(190) NULL,
  email_verified_at    TIMESTAMP NULL,
  status               ENUM('active','suspended','pending') NOT NULL DEFAULT 'active',
  last_login_at        TIMESTAMP NULL,
  created_at           TIMESTAMP NULL,
  updated_at           TIMESTAMP NULL,
  INDEX idx_status (status)
);
```

Why single-email-globally: Phase 1 explicitly rejects multi-store-per-user
(see "out of scope"). One person = one merchant = one account. Future
multi-store will lift the `UNIQUE` and rely on `gateway_user_tenants` as
the m2m bridge.

### 4.2 `gateway_user_tenants`

Bridge table — the *only* place where "which tenant does this user
belong to" lives. Already plural so future multi-store is a flag flip.

```sql
CREATE TABLE gateway_user_tenants (
  id                   BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id              BIGINT UNSIGNED NOT NULL,
  tenant_id            CHAR(36) NOT NULL,            -- matches tenants.id (UUID)
  tenant_subdomain     VARCHAR(64) NOT NULL,
  role                 ENUM('owner','manager','cashier') NOT NULL DEFAULT 'owner',
  is_owner             TINYINT(1) NOT NULL DEFAULT 0,
  created_at           TIMESTAMP NULL,
  updated_at           TIMESTAMP NULL,
  UNIQUE KEY uq_user_tenant (user_id, tenant_id),
  INDEX idx_tenant (tenant_id),
  CONSTRAINT fk_gut_user   FOREIGN KEY (user_id)   REFERENCES gateway_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_gut_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)       ON DELETE CASCADE
);
```

`tenant_id` deliberately mirrors `tenants.id` exactly (CHAR(36) UUID in
stancl/tenancy default). FK to `tenants` keeps the bridge in sync if a
tenant is hard-deleted.

### 4.3 `gateway_tenants`

Augments stancl `tenants` with gateway-only metadata. We keep these in a
side table rather than adding columns to `tenants` so we don't fight
stancl's `getCustomColumns()` contract.

```sql
CREATE TABLE gateway_tenants (
  tenant_id            CHAR(36) PRIMARY KEY,
  subdomain            VARCHAR(64) NOT NULL UNIQUE,
  store_name           VARCHAR(190) NOT NULL,
  owner_user_id        BIGINT UNSIGNED NOT NULL,
  status               ENUM('provisioning','active','suspended','failed') NOT NULL DEFAULT 'provisioning',
  provisioning_error   TEXT NULL,
  created_at           TIMESTAMP NULL,
  updated_at           TIMESTAMP NULL,
  CONSTRAINT fk_gt_tenant FOREIGN KEY (tenant_id)     REFERENCES tenants(id)        ON DELETE CASCADE,
  CONSTRAINT fk_gt_owner  FOREIGN KEY (owner_user_id) REFERENCES gateway_users(id)
);
```

### 4.4 `gateway_signup_attempts`

Rate-limit + abuse audit log.

```sql
CREATE TABLE gateway_signup_attempts (
  id                   BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  email                VARCHAR(190) NULL,
  ip                   VARCHAR(45) NOT NULL,
  user_agent           VARCHAR(500) NULL,
  succeeded            TINYINT(1) NOT NULL DEFAULT 0,
  failure_reason       VARCHAR(120) NULL,
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_time (ip, created_at),
  INDEX idx_email_time (email, created_at)
);
```

We rate-limit signups by IP (3/hour, 10/day) and login failures by email
(5/15min, lock at 10). Login attempts share this table with a separate
`failure_reason='login_failed'` marker — saves a fifth table.

### 4.5 Token revocation list (Redis, not MySQL)

Per spec, `POST /logout` is "stateless logout fine for v1" with TTL = remaining JWT exp. We use Redis keys `gateway:revoked:<jti>` with TTL = `exp - now()`. No table needed; key falls out automatically.

---

## 5. Endpoints

| Method | Path                          | Auth        | Rate limit                         |
|--------|-------------------------------|-------------|------------------------------------|
| POST   | `/api/auth/signup`            | none        | 3/IP/hour, 10/IP/day               |
| POST   | `/api/auth/login`             | none        | 5/email/15min, lock at 10          |
| POST   | `/api/auth/logout`            | Bearer JWT  | 60/IP/min                          |
| GET    | `/api/auth/me`                | Bearer JWT  | 120/IP/min                         |
| POST   | `/api/auth/forgot-password`   | none        | 3/email/hour (always returns 200)  |

All return JSON. Errors follow `{ "error": "code", "message": "human" }`.

### 5.1 Signup steps (transactional boundary noted)

1. Validate `email` format + `password` ≥8 chars w/ mixed case + digit.
2. **Slugify** `store_name` → `subdomain`: lowercase, ASCII-only, dashes,
   max 40 chars. If collision in `gateway_tenants.subdomain`, append
   `-XXXX` (4 random alnum lowercase chars). Retry up to 5 times before
   500. Reserved words blocked: `www`, `admin`, `api`, `mail`, `dev`,
   `staging`, `kimono`, `mmfood`, `a6`, `fujiba`, `snssale`, `pos`.
3. Within DB::transaction (central):
   - `INSERT gateway_users` (bcrypt cost 12, email_verified_at = NOW()
     for Phase 1).
   - `INSERT tenants` via `Tenant::create()` (UUID auto-generated by
     stancl).
   - `INSERT gateway_tenants` with status='provisioning'.
   - `INSERT gateway_user_tenants` (role=owner, is_owner=1).
   - Attach domain row: `$tenant->domains()->create(['domain' =>
     "{$subdomain}.snssale.com"])`.
4. **Outside the transaction** (so a partial provision is visible and
   recoverable): `Artisan::call('tenants:migrate', ['--tenants' =>
   [$tenant->id], '--force' => true])`. This is exactly what
   `CloneTenantJob` does (line 51-57) — proven path.
5. **Inside the new tenant** (tenancy bootstrap), seed:
   - 1 channel (code='default', name=store_name, currency=JPY,
     locale='ja').
   - 1 inventory_source (code='default', name='Default Warehouse').
   - 1 pos_outlet (linked to channel).
   - Tax categories: `tax_8_reduced` (8%) + `tax_10_standard` (10%) —
     two `tax_categories` rows + two `tax_rates` rows + bridge.
   - 1 pos_user (email=signup email, hashed pw, role='owner').
6. Mark `gateway_tenants.status='active'`. If step 4 or 5 fails, mark
   `status='failed'` + write `provisioning_error`. Tenant row stays
   (deletable manually); user gets a friendly retry message.
7. Generate JWT and return.

If step 3 fails: roll back the central transaction (no orphan tenant
DB). If step 4 fails: tenant row exists but DB doesn't — mark
status='failed', surface to user. If step 5 fails: same; admins can
re-run the seed manually.

### 5.2 Login steps

1. `gateway_users` lookup by email.
2. `Hash::check` on password. Failed attempt → log to
   `gateway_signup_attempts`. If email has 10+ failures in last 15min →
   reject with lockout message (don't say "wrong password" so attackers
   can't enumerate).
3. Update `last_login_at`.
4. Read `gateway_user_tenants` for this user (Phase 1: assert exactly
   one row, else 500). Pull subdomain from `gateway_tenants`.
5. Issue JWT.

### 5.3 JWT shape

```
Header:  { alg: "RS256", kid: "gateway-2026-05-11" }
Claims:  {
  iss: "snssale.com",
  aud: "snssale.com/tenants",
  sub: "<gateway_users.id>",
  jti: "<random uuid>",
  email: "...",
  name: "...",
  tenant_id: "<uuid>",
  tenant_subdomain: "kimono",
  role: "owner",
  iat: <unix>,
  exp: <unix + 86400>
}
```

24h expiry, RS256, kid in header for key rotation later. Key files:

- Private: `storage/auth-gateway/jwt-private.pem` (0600, www-data only)
- Public:  `storage/auth-gateway/jwt-public.pem`  (readable by tenant
  middleware via direct file read — both run as www-data on the same
  box in Phase 1)

Generated on first boot via `php artisan auth-gateway:keygen`. Idempotent.

---

## 6. Domain-scoped loading

The gateway must NOT register routes or middleware on tenant subdomains
(`kimono.snssale.com` etc.). Two-step gate:

1. `AuthGatewayServiceProvider::boot()` registers routes only when
   `tenancy()->initialized` is **false** AND request host is in
   `config('tenancy.central_domains')`.
2. As belt-and-braces: routes themselves are wrapped in a `Domain('snssale.com|www.snssale.com')` group at registration time.

This mirrors the existing pattern used by `App\Providers\SuperAdminServiceProvider`.

---

## 7. Tenant-side change (the only one)

A new middleware in `packages/Webkul/Pos/src/Http/Middleware/VerifyGatewayJwt.php`:

- Reads `Authorization: Bearer <jwt>`.
- Validates RS256 signature with public key.
- Asserts `tenant_id` claim matches `tenant()->id` (stancl-resolved from
  the host).
- On match: sets `auth('pos_user')` to the `pos_users` row keyed by
  `email = jwt.email` (Phase 1; later we'll move to `gateway_user_id`
  column on `pos_users` once we want SSO across multiple tenants).
- On mismatch: 403.

Order in the middleware stack: **before** `auth:pos_user` so the gateway
JWT short-circuits the existing username/password REST login. The
existing REST login (`POST /api/v1/pos/login`) keeps working
unchanged so we don't break the four LIVE tenants during rollout.

---

## 8. Existing-tenant migration

`php artisan gateway:import-existing-tenants [--tenant=ID] [--dry-run]`

For each target tenant:

1. Bootstrap into that tenant.
2. Read `pos_users` rows.
3. Per row: insert/skip into `gateway_users` (skip on email conflict +
   log), insert `gateway_user_tenants(role from pos_users.role,
   is_owner if role='owner')`.
4. Insert `gateway_tenants` row from `tenants` + `domains`.

Idempotent. Default targets: `tenant1` (`7016ca8c-...`) and `tenant2`
(`855c3286-...`). Production tenants explicitly excluded by ID
allowlist; `--allow-prod` flag required to even attempt them.

---

## 9. Native app changes (summary — full detail in
   pos-native PR)

- Hardcoded env: `EXPO_PUBLIC_GATEWAY_URL=https://snssale.com`
- Splash → `WelcomeScreen` (refactored): two CTAs, "Sign in" /
  "Create new store".
- New `SignupScreen` (email + password + store_name).
- `LoginScreen` already exists — repoint URL from per-tenant pos login
  to gateway login.
- After auth: store `{ jwt, tenant_subdomain, gateway_user_id, role,
  store_name }` in `expo-secure-store` under key `gateway.session`.
- Apollo + REST clients: base URL becomes
  `https://${tenant_subdomain}.snssale.com`. Gateway URL only used
  during auth.
- Server picker (`useServerList`) removed; `ServerStep.tsx` deleted from
  Welcome flow.

---

## 10. Out of scope (Phase 1)

| Item                             | Why deferred                                        |
|----------------------------------|-----------------------------------------------------|
| Email verification               | No SMTP wiring yet; auto-verify Phase 1             |
| Password reset email             | Stub returns 200; real email Phase 4                |
| Phone verification               | No SMS provider                                     |
| Subscription billing             | Free for everyone                                   |
| Multi-store-per-user             | Schema is m2m-ready, UI not                         |
| Refresh tokens                   | 24h JWT acceptable; Phase 4 will add `/refresh`     |
| KYC                              | Manual review queue Phase 4                         |
| Wildcard TLS automation          | Operator handles cert; SslService runs async        |
| Email enumeration resistance     | Forgot-password 200-always covers it                |

---

## 11. Risks + mitigations

| Risk                                                | Mitigation                                                         |
|----------------------------------------------------|--------------------------------------------------------------------|
| Tenant migrate failure leaves orphan central row    | Status='failed' + `provisioning_error` column; admins clean up     |
| Slug collision on common store names               | 4-char suffix retry; reserved-word block list                      |
| User signs up twice with same email                | UNIQUE on `gateway_users.email` returns friendly "already exists"  |
| JWT private key leak                               | 0600 perms; key rotation via `kid` claim later                     |
| Race: two simultaneous signups grab same slug      | UNIQUE on `gateway_tenants.subdomain` rejects loser; retry loop    |
| Provisioning takes >30s (timeout)                  | Phase 1 acceptable (synchronous); Phase 2 will queue              |

---

## 12. Phase 1 acceptance criteria

1. `curl -d '{...}' snssale.com/api/auth/signup` creates a new tenant DB,
   returns a JWT.
2. `curl -d '{...}' snssale.com/api/auth/login` returns same shape JWT.
3. Native web demo: sign-up → land on Home → take a cash sale → see in
   order list. Logout → log back in → still works.
4. `gateway:import-existing-tenants` runs cleanly on staging (tenant1 +
   tenant2).
5. JWT issued by gateway is accepted by tenant `/api/v1/pos/me` (or
   GraphQL `currentSessionAgent`).
6. `kimono`/`mmfood`/`a6`/`fujiba` continue to work via existing REST
   login. (No regression.)
