<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminPermission
{
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        $sa = Auth::guard('superadmin')->user();
        if (! $sa) {
            return redirect()->route('superadmin.login');
        }

        if (! $sa->can($permissionKey)) {
            abort(403, "Super-admin permission '{$permissionKey}' is required for this action.");
        }

        return $next($request);
    }
}
