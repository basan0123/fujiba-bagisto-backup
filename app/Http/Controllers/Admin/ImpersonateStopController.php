<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdminImpersonationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateStopController extends Controller
{
    public function __invoke(Request $request)
    {
        $imp = $request->session()->get('impersonation');

        // Close the central-DB log row (it lives in central, not tenant).
        if ($imp && ! empty($imp['log_id'])) {
            try {
                $log = SuperAdminImpersonationLog::on('mysql')->find($imp['log_id']);
                if ($log && ! $log->ended_at) {
                    $log->ended_at = now();
                    $log->save();
                }
            } catch (\Throwable $e) {
                // central DB may not be reachable from tenant context; soft-fail.
            }
        }

        Auth::guard('admin')->logout();
        $request->session()->forget('impersonation');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to central super-admin dashboard.
        return redirect(rtrim(config('app.url'), '/') . '/superadmin/dashboard');
    }
}
