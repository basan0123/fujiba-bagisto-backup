<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Stancl\Tenancy\Tenancy;
use Stancl\Tenancy\Database\Models\Domain;

class InitializeTenancyForSubdomains
{
    public function __construct(protected Tenancy $tenancy) {}

    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        // Skip tenant init if on a central domain (snssale.com / www. / etc.).
        //
        // Central domains have NO tenant DB, so the central connection is used
        // — and that DB has no `channels`/`currencies` rows. Any request that
        // reaches the Shop storefront there dereferences core()->getCurrentChannel()
        // (null) and fatals with HTTP 500 ("Attempt to read property base_currency
        // on null", Core.php:413). The apex-guard *route* in routes/web.php cannot
        // prevent this because the Shop package's `GET /` route registers first
        // and wins route matching. So we guard here, in global middleware, which
        // runs BEFORE any controller is dispatched and is therefore immune to
        // route registration order.
        if (in_array($host, $centralDomains)) {
            return $this->guardCentralDomain($request, $next);
        }

        // Find the tenant by domain lookup (works for ANY domain pattern,
        // not just *.snssale.com — supports fujiba.shop, custom domains, etc.)
        $domain = Domain::where('domain', $host)->first();

        if (!$domain) {
            // Not a registered tenant domain — continue as central
            return $next($request);
        }

        $tenant = $domain->tenant;

        if (!$tenant->status) {
            abort(403, 'This store is suspended.');
        }

        // Switch to Tenant Database
        $this->tenancy->initialize($tenant);

        // Resolve channel from tenant DB.
        // Strategy: first try exact hostname match, then try matching by
        // channel code prefix in the domain (e.g. "mmfood.fujiba.co.jp" → channel "mmfood"),
        // then fall back to the default channel.
        $channelRepo = app(\Webkul\Core\Repositories\ChannelRepository::class);

        $channel = $channelRepo->findWhereIn('hostname', [
            $host,
            'http://' . $host,
            'https://' . $host,
        ])->first();

        if (! $channel) {
            // Try matching by channel code in domain prefix.
            // e.g. "mmfood.fujiba.co.jp" should match channel with code "mmfood"
            $subdomain = explode('.', $host)[0] ?? '';
            $allChannels = $channelRepo->all();

            foreach ($allChannels as $ch) {
                if ($ch->code !== 'default' && strcasecmp($subdomain, $ch->code) === 0) {
                    $channel = $ch;
                    break;
                }
            }

            // Fall back to default channel
            if (! $channel) {
                $channel = $allChannels->first();
            }
        }

        if ($channel) {
            core()->setCurrentChannel($channel);
        }

        $tenantId = $tenant->id;

        // Isolate session storage per tenant
        $sessionPath = storage_path('framework/sessions/' . $tenantId);
        if (!File::exists($sessionPath)) {
            File::makeDirectory($sessionPath, 0755, true);
        }
        Config::set('session.files', $sessionPath);

        // Isolate session cookie and cache prefix
        Config::set('session.cookie', 'bagisto_' . substr($tenantId, 0, 8) . '_session');
        Config::set('cache.prefix', 'tenant_' . substr($tenantId, 0, 8));

        return $next($request);
    }

    /**
     * Handle a request on a central (non-tenant) domain.
     *
     * The central DB has no storefront data, so we never let the request fall
     * through to the Shop storefront. Control-plane paths (super-admin panel,
     * tenant-aware storage, impersonation, language/messenger/chatpion helpers,
     * and framework internals) are allowed through; the bare apex redirects to
     * the super-admin login; everything else gets a branded standalone 404.
     */
    protected function guardCentralDomain(Request $request, Closure $next)
    {
        $path = trim($request->path(), '/'); // '' for the bare apex "/"

        // Bare apex → operational destination (SaaS control panel login).
        if ($path === '') {
            return redirect('/superadmin/login');
        }

        // Path prefixes that legitimately exist on the central domain. Any
        // request matching one of these is dispatched normally.
        $allowedPrefixes = [
            // Central auth gateway (signup/login/logout/me/forgot-password/health).
            // These routes are registered by Fujiba\AuthGateway on the gateway
            // domains (snssale.com etc., a SUBSET of central_domains) and MUST
            // resolve here — signup CREATES the tenant, so it cannot fall back to
            // a per-tenant endpoint. Without this entry guardCentralDomain() emits
            // the branded "Shop not found" 404 before the gateway routes dispatch
            // (global middleware runs before route matching). See
            // packages/Fujiba/AuthGateway/routes/api.php.
            'api/auth',
            'superadmin',
            'storage',
            'language',
            'messenger',
            'customer/messenger',
            'chatpion',
            'admin',                  // impersonation accept/stop + admin url
            config('app.admin_url'),  // configured admin slug (may differ from "admin")
            // Framework / tooling internals that must never be 404'd:
            'livewire',
            '_debugbar',
            'telescope',
            'horizon',
            'sanctum',
            'broadcasting',
            'up',                     // Laravel health check
        ];

        foreach ($allowedPrefixes as $prefix) {
            $prefix = trim((string) $prefix, '/');

            if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'))) {
                return $next($request);
            }
        }

        // Anything else would try to render the storefront (which fatals on the
        // empty central DB). Serve a branded 404 instead.
        return response()->view('errors.tenant-not-found', [], 404);
    }
}
