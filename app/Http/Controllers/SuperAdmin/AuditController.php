<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdminAuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $q = SuperAdminAuditLog::query()->orderByDesc('created_at');

        if ($action = $request->query('action')) {
            $q->where('action', 'like', $action.'%');
        }
        if ($email = $request->query('actor')) {
            $q->where('actor_email', 'like', '%'.$email.'%');
        }
        if ($tenant = $request->query('tenant')) {
            $q->where('target_tenant_id', $tenant);
        }

        $logs = $q->paginate(50)->withQueryString();

        return view('superadmin.audit.index', compact('logs'));
    }
}
