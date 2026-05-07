<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('domains')->latest()->paginate(20);
        return view('superadmin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        $plans = DB::table('plans')->where('is_active', true)->get();
        return view('superadmin.tenants.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:tenants',
            'subdomain' => 'required|alpha_dash',
            'plan'      => 'required|string',
        ]);

        $subdomain = strtolower($request->subdomain) . '.162.43.75.135.nip.io';

        if (DB::table('domains')->where('domain', $subdomain)->exists()) {
            return back()->withErrors(['subdomain' => 'Subdomain already taken.'])->withInput();
        }

        // Create tenant record in central DB
        $tenant = Tenant::create([
            'name'   => $request->name,
            'email'  => $request->email,
            'plan'   => $request->plan,
            'status' => true,
        ]);

        $tenant->domains()->create(['domain' => $subdomain]);

        // Clone master template into new tenant DB
        $tenantDb = 'tenant_' . $tenant->id;
        $this->cloneFromMaster($tenantDb);

        return redirect()->route('superadmin.tenants.index')
            ->with('success', "✅ Tenant '{$request->name}' created at http://{$subdomain}");
    }

    protected function cloneFromMaster(string $targetDb): void
    {
        $master = 'tenant_master_template';
        $user   = config('database.connections.mysql.username');
        $pass   = config('database.connections.mysql.password');
        $host   = config('database.connections.mysql.host', '127.0.0.1');

        // Ensure target DB exists
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$targetDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Dump master and import into new tenant DB
        $cmd = "mysqldump -u{$user} -p{$pass} -h{$host} {$master} 2>/dev/null | mysql -u{$user} -p{$pass} -h{$host} {$targetDb} 2>/dev/null";
        shell_exec($cmd);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('domains');

        // Pull usage from the tenant DB. Best-effort — failures show '—'.
        $usage = ['products' => null, 'orders' => null, 'customers' => null, 'storage_mb' => null, 'channels' => null];
        try {
            $db = $tenant->database()->getName();
            $usage['products']  = (int) DB::selectOne("SELECT COUNT(*) c FROM `{$db}`.products")?->c;
            $usage['orders']    = (int) DB::selectOne("SELECT COUNT(*) c FROM `{$db}`.orders")?->c;
            $usage['customers'] = (int) DB::selectOne("SELECT COUNT(*) c FROM `{$db}`.customers")?->c;
            $usage['channels']  = (int) DB::selectOne("SELECT COUNT(*) c FROM `{$db}`.channels")?->c;
            $size = DB::selectOne(
                "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) mb
                 FROM information_schema.tables WHERE table_schema = ?",
                [$db]
            );
            $usage['storage_mb'] = $size?->mb;
        } catch (\Throwable $e) {
            // tenant DB may be unavailable; carry on with null usage.
        }

        $plan = null;
        if ($tenant->plan_id) {
            $plan = DB::table('plans')->find($tenant->plan_id);
            if ($plan && is_string($plan->features)) {
                $plan->features = json_decode($plan->features, true);
            }
        }

        return view('superadmin.tenants.show', compact('tenant', 'usage', 'plan'));
    }

    public function toggleStatus(Tenant $tenant)
    {
        $tenant->update(['status' => !$tenant->status]);
        app(\App\Services\SuperAdmin\AuditWriter::class)->log(
            auth('superadmin')->user(), 'tenant.toggle', $tenant->id, null, ['status' => $tenant->status]
        );
        return back()->with('success', 'Tenant status updated.');
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->update(['suspended_at' => now(), 'status' => false]);
        app(\App\Services\SuperAdmin\AuditWriter::class)->log(
            auth('superadmin')->user(), 'tenant.suspended', $tenant->id, null, null
        );
        return back()->with('success', 'Tenant suspended.');
    }

    public function unsuspend(Tenant $tenant)
    {
        $tenant->update(['suspended_at' => null, 'status' => true]);
        app(\App\Services\SuperAdmin\AuditWriter::class)->log(
            auth('superadmin')->user(), 'tenant.unsuspended', $tenant->id, null, null
        );
        return back()->with('success', 'Tenant unsuspended.');
    }

    public function destroy(Tenant $tenant)
    {
        // Soft delete first (30-day grace). Hard-delete is a separate Phase 6
        // command run by `tenants:purge-soft-deleted`.
        $tenant->update(['deleted_at' => now(), 'status' => false]);
        app(\App\Services\SuperAdmin\AuditWriter::class)->log(
            auth('superadmin')->user(), 'tenant.soft_deleted', $tenant->id, null, null
        );
        return redirect()->route('superadmin.tenants.index')
            ->with('success', 'Tenant soft-deleted (30-day grace before hard delete).');
    }
}
