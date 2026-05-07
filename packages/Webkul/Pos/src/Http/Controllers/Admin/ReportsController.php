<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Webkul\Pos\DataGrids\Admin\ReportDataGrid;

class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ReportDataGrid::class)->process();
        }

        return view('pos::admin.sales-reports.index');
    }
}
