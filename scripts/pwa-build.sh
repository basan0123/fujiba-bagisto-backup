#!/usr/bin/env bash
# =============================================================================
# /var/www/bagisto/scripts/pwa-build.sh
# Rebuild Bagisto PWA from source (Laravel Mix 5 + webpack 4 + Vue 2) and
# deploy to public/.  Use this after editing any .vue / .js / .scss source.
#
# Source dir:   /var/www/bagisto/storage/pwa-build/source  (writable copy)
# Vendor mirror: /var/www/bagisto/vendor/bagisto/pwa       (kept in sync so
#                composer install doesn't lose changes)
#
# Usage:
#   bash /var/www/bagisto/scripts/pwa-build.sh
# =============================================================================
set -euo pipefail

WORK=/var/www/bagisto/storage/pwa-build/source
VENDOR=/var/www/bagisto/vendor/bagisto/pwa
PUB=/var/www/bagisto/public/themes/pwa/default/build/assets
SW=/var/www/bagisto/public/service-worker.js
TS=$(date +%Y%m%d-%H%M%S)
SNAP=/var/www/bagisto/storage/pwa-build-snapshots/$TS

if [ ! -d "$WORK" ]; then
    echo "❌ Working source dir missing: $WORK"
    exit 1
fi

# Snapshot current public assets before overwriting
mkdir -p "$SNAP"
cp -a /var/www/bagisto/public/themes/pwa/default/build "$SNAP/build"
cp -a "$SW" "$SNAP/service-worker.js" 2>/dev/null || true
echo "📦 Snapshot: $SNAP"

# ── Rebuild ────────────────────────────────────────────────────────────────
echo "🔨 Building (production)…"
cd "$WORK"
NODE_OPTIONS=--openssl-legacy-provider npm run prod 2>&1 | tail -8

# ── Deploy ─────────────────────────────────────────────────────────────────
cp "$WORK/publishable/assets/js/app.js"      "$PUB/js/app.js"
cp "$WORK/publishable/assets/js/app.js.LICENSE.txt" "$PUB/js/app.js.LICENSE.txt" 2>/dev/null || true
cp "$WORK/publishable/assets/css/pwa.css"    "$PUB/css/pwa.css"      2>/dev/null || true
cp "$WORK/publishable/assets/css/pwa-admin.css" "$PUB/css/pwa-admin.css" 2>/dev/null || true

# Service worker (separate from build output)
cp "$WORK/publishable/pwa/service-worker.js" "$SW"

# ── Apply runtime SW patch (idempotent) ────────────────────────────────────
python3 - <<'PY'
import re, sys
p = '/var/www/bagisto/public/service-worker.js'
s = open(p, encoding='utf-8').read()
if 'indexOf("/api/") >= 0' in s:
    sys.exit()
old = 'function skipIfThese(requestUrl) {\n    if (requestUrl.pathname.indexOf("/pwa") < 0'
new = 'function skipIfThese(requestUrl) {\n    if (requestUrl.pathname.indexOf("/api/") >= 0) { return true; }\n    if (requestUrl.pathname.indexOf("/pwa") < 0'
if old in s:
    s = s.replace(old, new, 1)
    s = re.sub(r"var version = 'v[0-9.]+';", "var version = 'v"+__import__("time").strftime("%y%m%d.%H%M")+"';", s, count=1)
    open(p, 'w', encoding='utf-8').write(s)
    print("SW: /api/ bypass applied + version bumped")
PY

# ── Mirror source back into vendor/ (so composer install doesn't lose it) ──
rsync -a --delete \
    --exclude=node_modules --exclude=publishable \
    "$WORK/src/" "$VENDOR/src/"
echo "✅ Vendor source synced"

# ── Permissions + cache bust ───────────────────────────────────────────────
chown -R www-data:www-data /var/www/bagisto/public/themes/pwa/default/build "$SW"
touch "$PUB/js/app.js" "$SW"
node --check "$PUB/js/app.js" >/dev/null && echo "✅ Syntax OK" || echo "❌ SYNTAX FAILED"

cd /var/www/bagisto
php artisan view:clear >/dev/null 2>&1 || true

echo
echo "Done. Bundle: $(stat -c%s $PUB/js/app.js) bytes"
echo "Restore: rm -rf $PUB && cp -a $SNAP/build/assets $PUB/.."
