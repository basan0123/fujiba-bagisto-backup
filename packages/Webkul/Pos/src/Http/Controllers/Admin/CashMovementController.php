<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Pos\DataGrids\Admin\CashMovementDataGrid;
use Webkul\Pos\Repositories\CashMovementRepository;
use Webkul\Pos\Repositories\OutletRepository;

class CashMovementController extends Controller
{
    public function __construct(
        protected CashMovementRepository $cashMovementRepository,
        protected OutletRepository       $outletRepository,
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(CashMovementDataGrid::class)->process();
        }

        $outlets = $this->outletRepository->all();

        return view('pos::admin.cash-movements.index', compact('outlets'));
    }

    public function create()
    {
        $outlets = $this->outletRepository->all();

        return view('pos::admin.cash-movements.create', compact('outlets'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'outlet_id'        => 'nullable|exists:pos_outlets,id',
            'type'             => 'required|in:income,expense',
            'category'         => 'required|string|max:50',
            'description'      => 'nullable|string|max:255',
            'amount'           => 'required|numeric|min:0.01',
            'movement_date'    => 'required|date',
            'reference_number' => 'nullable|string|max:100',
        ]);

        $data['currency'] = core()->getBaseCurrencyCode();

        $this->cashMovementRepository->create($data);

        return to_route('admin.pos.cash-movements.index')
            ->with('success', trans('pos::app.admin.cash-movements.create-success'));
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->cashMovementRepository->delete($id);

            return new JsonResponse([
                'message' => trans('pos::app.admin.cash-movements.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }
    }
}
