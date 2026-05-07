<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    public function index()
    {
        $plans = DB::table('plans')->latest()->get();
        return view('superadmin.plans.index', compact('plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        DB::table('plans')->insert([
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
            'features' => json_encode($request->features ?? []),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Plan created.');
    }

    public function destroy($id)
    {
        DB::table('plans')->where('id', $id)->delete();
        return back()->with('success', 'Plan deleted.');
    }
}
