<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Plan;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', true)->count(),
            'total_plans' => \DB::table('plans')->count(),
            'recent_tenants' => Tenant::with('domains')->latest()->take(5)->get(),
        ];
        return view('superadmin.dashboard.index', compact('stats'));
    }
}
