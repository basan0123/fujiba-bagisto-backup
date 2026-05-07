<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\DataGrids\Catalog\StockAuditDataGrid;
use Webkul\Admin\Http\Controllers\Controller;

class StockAuditController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(StockAuditDataGrid::class)->process();
        }

        return view('admin::catalog.track.index');
    }

    public function editRemark(int $id)
    {
        $audit = DB::table('product_stock_audits as psa')
            ->leftJoin('product_flat as pf', function ($j) {
                $j->on('pf.product_id', '=', 'psa.product_id')
                    ->where('pf.locale', '=', app()->getLocale());
            })
            ->leftJoin('inventory_sources as is_', 'is_.id', '=', 'psa.inventory_source_id')
            ->leftJoin('admins as a', 'a.id', '=', 'psa.admin_id')
            ->leftJoin('admins as ar', 'ar.id', '=', 'psa.remark_updated_by')
            ->select(
                'psa.*',
                'pf.sku',
                'pf.name as product_name',
                'is_.name as source_name',
                'a.name as admin_name',
                'ar.name as remark_updater_name',
            )
            ->where('psa.id', $id)
            ->first();

        if (! $audit) {
            abort(404);
        }

        return view('admin::catalog.track.edit-remark', compact('audit'));
    }

    public function updateRemark(Request $request, int $id)
    {
        $request->validate([
            'remark' => 'nullable|string|max:2000',
        ]);

        $exists = DB::table('product_stock_audits')->where('id', $id)->exists();
        if (! $exists) {
            abort(404);
        }

        $adminId = auth()->guard('admin')->user()?->id;

        DB::table('product_stock_audits')
            ->where('id', $id)
            ->update([
                'remark' => $request->input('remark'),
                'remark_updated_by' => $adminId,
                'remark_updated_at' => now(),
                'updated_at' => now(),
            ]);

        session()->flash('success', 'Remark saved.');

        return redirect()->route('admin.catalog.track.index');
    }

    public function massUpdateRemark(Request $request): JsonResponse
    {
        $request->validate([
            'indices' => 'required|array|min:1',
            'indices.*' => 'integer',
            'value' => 'required|string|max:2000',
        ]);

        $remark = $request->input('value');
        if ($remark === '__CLEAR__') {
            $remark = null;
        }

        $adminId = auth()->guard('admin')->user()?->id;

        $count = DB::table('product_stock_audits')
            ->whereIn('id', $request->input('indices'))
            ->update([
                'remark' => $remark,
                'remark_updated_by' => $adminId,
                'remark_updated_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => $count.' remark(s) updated.',
        ]);
    }

    public function bulkUpdateRemark(Request $request): JsonResponse
    {
        $request->validate([
            'indices' => 'required|array|min:1',
            'indices.*' => 'integer',
            'remark' => 'nullable|string|max:2000',
        ]);

        $adminId = auth()->guard('admin')->user()?->id;

        $count = DB::table('product_stock_audits')
            ->whereIn('id', $request->input('indices'))
            ->update([
                'remark' => $request->input('remark'),
                'remark_updated_by' => $adminId,
                'remark_updated_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => $count.' remark(s) updated.',
        ]);
    }
}
