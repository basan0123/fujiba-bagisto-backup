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

        // Skip if on a central domain
        if (in_array($host, $centralDomains)) {
            return $next($request);
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
}
