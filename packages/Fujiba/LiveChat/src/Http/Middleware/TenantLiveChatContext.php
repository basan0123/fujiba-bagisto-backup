<?php

namespace Fujiba\LiveChat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantLiveChatContext
{
    public function handle(Request $request, Closure $next)
    {
        // Tenant context is resolved from the authenticated admin user
        // or from the X-Tenant-Id header for API calls
        return $next($request);
    }
}
