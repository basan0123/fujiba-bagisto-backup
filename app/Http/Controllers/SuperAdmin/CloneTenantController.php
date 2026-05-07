<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\CloneTenantJob;
use App\Models\Tenant;
use App\Models\TenantProvisioningJob;
use App\Services\SuperAdmin\AuditWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CloneTenantController extends Controller
{
    public function __construct(private AuditWriter $audit) {}

    public function create()
    {
        $tenants = Tenant::with('domains')
            ->whereNull('deleted_at')
            ->whereNull('suspended_at')
            ->get();

        $plans = DB::table('plans')->where('is_active', true)->get();

        return view('superadmin.tenants.clone_create', compact('tenants', 'plans'));
    }

    /**
     * AJAX: list channels in a source tenant DB.
     */
    public function sourceChannels(Tenant $tenant)
    {
        $db = $tenant->database()->getName();

        $rows = DB::connection('mysql')
            ->select("SELECT id, code, root_category_id FROM `{$db}`.channels ORDER BY id ASC");

        return response()->json(['channels' => $rows]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:120',
            'domain'            => 'required|string|max:255|regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i',
            'owner_email'       => 'required|email|max:255',
            'admin_email'       => 'required|email|max:255',
            'admin_password'    => 'required|string|min:8|max:120',
            'plan_id'           => 'required|integer|exists:plans,id',
            'source_tenant_id'  => 'required|string|exists:tenants,id',
            'source_channels'   => 'required|array|min:1',
            'source_channels.*' => 'string|max:64',
            'copy_images'       => 'required|in:0,1',
        ]);

        // Domain uniqueness (across all tenants, not just current).
        if (DB::table('domains')->where('domain', $data['domain'])->exists()) {
            return back()->withErrors(['domain' => 'Domain already in use by another tenant.'])->withInput();
        }

        $newTenantId = (string) Str::uuid();

        $job = TenantProvisioningJob::create([
            'tenant_id'        => $newTenantId,
            'domain'           => $data['domain'],
            'source_tenant_id' => $data['source_tenant_id'],
            'source_channels'  => array_values($data['source_channels']),
            'admin_email'      => $data['admin_email'],
            'plan_id'          => (int) $data['plan_id'],
            'initiated_by'     => auth('superadmin')->id(),
            'overall_status'   => 'pending',
            'steps'            => TenantProvisioningJob::freshStepsArray(),
            'last_message'     => 'Queued',
        ]);

        $this->audit->log(
            auth('superadmin')->user(),
            'tenant.clone_started',
            $newTenantId,
            null,
            [
                'domain'           => $data['domain'],
                'source_tenant_id' => $data['source_tenant_id'],
                'source_channels'  => $data['source_channels'],
            ]
        );

        CloneTenantJob::dispatch(
            jobId: $job->id,
            newTenantId: $newTenantId,
            payload: [
                'name'             => $data['name'],
                'domain'           => $data['domain'],
                'owner_email'      => $data['owner_email'],
                'admin_email'      => $data['admin_email'],
                'admin_password'   => $data['admin_password'],
                'plan_id'          => (int) $data['plan_id'],
                'source_tenant_id' => $data['source_tenant_id'],
                'source_channels'  => array_values($data['source_channels']),
                'copy_images'      => (bool) $data['copy_images'],
            ]
        );

        return redirect()->route('superadmin.tenants.clone.watch', $job->id);
    }

    public function watch(int $job)
    {
        $job = TenantProvisioningJob::findOrFail($job);
        return view('superadmin.tenants.clone_watch', compact('job'));
    }

    public function status(int $job)
    {
        $job = TenantProvisioningJob::findOrFail($job);
        return response()->json([
            'id'             => $job->id,
            'tenant_id'      => $job->tenant_id,
            'domain'         => $job->domain,
            'overall_status' => $job->overall_status,
            'last_message'   => $job->last_message,
            'steps'          => $job->steps,
            'started_at'     => $job->started_at,
            'finished_at'    => $job->finished_at,
            'login_url'      => $job->overall_status === 'done'
                ? 'http://' . $job->domain . '/admin/login'
                : null,
        ]);
    }
}
