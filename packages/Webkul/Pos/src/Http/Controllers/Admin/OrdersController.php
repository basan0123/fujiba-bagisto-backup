<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Webkul\Pos\DataGrids\Admin\OrderDataGrid;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(OrderDataGrid::class)->process();
        }

        return view('pos::admin.orders.index');
    }
}
