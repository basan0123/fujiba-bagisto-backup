<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Pos\DataGrids\Admin\FixedExpenseDataGrid;
use Webkul\Pos\Models\FixedExpenseApplication;
use Webkul\Pos\Repositories\FixedExpenseApplicationRepository;
use Webkul\Pos\Repositories\FixedExpenseRepository;
use Webkul\Pos\Repositories\OutletRepository;

class FixedExpenseController extends Controller
{
    public function __construct(
        protected FixedExpenseRepository            $fixedExpenseRepository,
        protected FixedExpenseApplicationRepository $applicationRepository,
        protected OutletRepository                  $outletRepository,
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(FixedExpenseDataGrid::class)->process();
        }

        $outlets = $this->outletRepository->all();

        return view('pos::admin.fixed-expenses.index', compact('outlets'));
    }

    public function create()
    {
        $outlets = $this->outletRepository->all();

        return view('pos::admin.fixed-expenses.create', compact('outlets'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'outlet_id'   => 'nullable|exists:pos_outlets,id',
            'name'        => 'required|string|max:191',
            'category'    => 'required|string|max:50',
            'amount'      => 'required|numeric|min:0',
            'billing_day' => 'required|integer|min:1|max:31',
            'is_active'   => 'boolean',
            'notes'       => 'nullable|string',
        ]);

        $data['currency']  = core()->getBaseCurrencyCode();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->fixedExpenseRepository->create($data);

        return to_route('admin.pos.fixed-expenses.index')
            ->with('success', trans('pos::app.admin.fixed-expenses.create-success'));
    }

    public function edit(int $id)
    {
        $expense = $this->fixedExpenseRepository->findOrFail($id);
        $outlets = $this->outletRepository->all();

        return view('pos::admin.fixed-expenses.edit', compact('expense', 'outlets'));
    }

    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'outlet_id'   => 'nullable|exists:pos_outlets,id',
            'name'        => 'required|string|max:191',
            'category'    => 'required|string|max:50',
            'amount'      => 'required|numeric|min:0',
            'billing_day' => 'required|integer|min:1|max:31',
            'is_active'   => 'boolean',
            'notes'       => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $this->fixedExpenseRepository->update($data, $id);

        return to_route('admin.pos.fixed-expenses.index')
            ->with('success', trans('pos::app.admin.fixed-expenses.update-success'));
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->fixedExpenseRepository->delete($id);

            return new JsonResponse([
                'message' => trans('pos::app.admin.fixed-expenses.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Manually apply all active fixed expenses for a given month.
     * Called by scheduler OR manually from admin.
     */
    public function applyMonth(Request $request): JsonResponse
    {
        $month = $request->input('month', now()->format('Y-m'));

        $expenses = $this->fixedExpenseRepository->where('is_active', 1)->get();

        $applied = 0;

        foreach ($expenses as $expense) {
            $exists = $this->applicationRepository->where([
                'fixed_expense_id' => $expense->id,
                'month'            => $month,
            ])->exists();

            if (! $exists) {
                $this->applicationRepository->create([
                    'fixed_expense_id' => $expense->id,
                    'outlet_id'        => $expense->outlet_id,
                    'month'            => $month,
                    'applied_amount'   => $expense->amount,
                    'status'           => 'auto',
                    'applied_at'       => now(),
                ]);

                $applied++;
            }
        }

        return new JsonResponse([
            'message' => trans('pos::app.admin.fixed-expenses.apply-success', ['count' => $applied]),
            'applied' => $applied,
        ]);
    }
}
