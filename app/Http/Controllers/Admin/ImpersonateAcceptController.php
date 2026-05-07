<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Webkul\User\Models\Admin;

/**
 * Endpoint hit on the tenant domain after the super-admin clicks Impersonate.
 * The signed URL carries the target admin id; we authenticate that admin and
 * mark the session so the banner middleware shows the persistent red banner.
 *
 * The signature is verified by the signed-route middleware on the route
 * itself; the only thing we do here is honor it and log the admin in.
 */
class ImpersonateAcceptController extends Controller
{
    public function __invoke(Request $request)
    {
        $adminId = (int) $request->query('admin');
        $logId   = (int) $request->query('log');
        $saId    = (int) $request->query('sa');

        if (! $adminId || ! $logId || ! $saId) {
            abort(400, 'Missing impersonation parameters.');
        }

        // Tenant DB has been initialized by stancl/tenancy at this point —
        // Admin model resolves against the tenant connection.
        $admin = Admin::find($adminId);
        if (! $admin) {
            abort(404, 'Target admin not found in tenant.');
        }

        Auth::guard('admin')->login($admin);

        $request->session()->put('impersonation', [
            'log_id'    => $logId,
            'sa_id'     => $saId,
            'started_at'=> now()->toDateTimeString(),
        ]);

        Log::info('[Impersonation] accepted', [
            'admin_id' => $adminId,
            'log_id'   => $logId,
            'sa_id'    => $saId,
        ]);

        return redirect(config('app.admin_url') . '/dashboard');
    }
}
