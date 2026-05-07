<?php

namespace Webkul\Pos\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Pos
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        abort_if(! (bool) core()->getConfigData('pos.settings.general.status'), 404);

        return $next($request);
    }
}
