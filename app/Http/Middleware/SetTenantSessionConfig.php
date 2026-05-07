<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Runs BEFORE StartSession to set a unique session cookie name and file
 * storage path per tenant subdomain. Must be inserted before StartSession
 * in the global middleware stack (bootstrap/app.php).
 */
class SetTenantSessionConfig
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $baseDomain = config('app.base_domain', '162.43.75.135.nip.io');

        if (str_ends_with($host, '.' . $baseDomain)) {
            $subdomain = explode('.', $host)[0];

            // Unique cookie name prevents session bleed across subdomains
            config(['session.cookie' => 'bagisto_' . $subdomain . '_session']);

            // Unique files path isolates session files per tenant
            $path = storage_path('framework/sessions/' . $subdomain);

            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }

            config(['session.files' => $path]);
        }

        return $next($request);
    }
}
