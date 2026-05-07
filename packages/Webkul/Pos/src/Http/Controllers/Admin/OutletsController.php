<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Inventory\Repositories\InventorySourceRepository;
use Webkul\Pos\DataGrids\Admin\OutletDataGrid;
use Webkul\Pos\DataGrids\Admin\OutletProductDataGrid;
use Webkul\Pos\Http\Requests\OutletForm;
use Webkul\Pos\Repositories\OrderRepository;
use Webkul\Pos\Repositories\OutletProductRepository;
use Webkul\Pos\Repositories\OutletRepository;
use Webkul\Pos\Repositories\ReceiptRepository;
use Webkul\Product\Repositories\ProductRepository;

class OutletsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected ReceiptRepository $receiptRepository,
        protected InventorySourceRepository $inventorySourceRepository,
        protected OutletRepository $outletRepository,
        protected OutletProductRepository $outletProductRepository,
        protected ProductRepository $productRepository,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(OutletDataGrid::class)->process();
        }

        return view('pos::admin.users.outlets.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('pos::admin.users.outlets.create')
            ->with([
                'receipts'        => $this->receiptRepository->findWhere(['status' => 1]),
                'inventorySource' => $this->inventorySourceRepository->findWhere(['status' => 1]),
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(OutletForm $request)
    {
        $this->outletRepository->create($request->validated());

        return to_route('admin.pos.outlets.index')
            ->with('success', trans('pos::app.admin.users.outlets.create-success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $outlet = $this->outletRepository->findOrFail($id);

        return view('pos::admin.users.outlets.edit')
            ->with([
                'outlet'          => $outlet,
                'receipts'        => $this->receiptRepository->findWhere(['status' => 1]),
                'inventorySource' => $this->inventorySourceRepository->findWhere(['status' => 1]),
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(OutletForm $request, int $id)
    {
        $this->outletRepository->update($request->validated(), $id);

        return to_route('admin.pos.outlets.index')
            ->with('success', trans('pos::app.admin.users.outlets.update-success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->orderRepository->deleteWhere(['outlet_id' => $id]);

            $this->outletRepository->delete($id);

            return new JsonResponse([
                'message' => trans('pos::app.admin.users.outlets.delete-success'),
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('pos::app.admin.users.outlets.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass update the users
     */
    public function massUpdate(MassUpdateRequest $request): JsonResponse
    {
        $this->outletRepository
            ->whereIn('id', $request->input('indices'))
            ->update(['status' => $request->input('value')]);

        return new JsonResponse([
            'message' => trans('pos::app.admin.users.outlets.index.datagrid.mass-update-success'),
        ]);
    }

    /**
     * Mass Delete the outlets
     */
    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        try {
            $this->orderRepository
                ->whereIn('outlet_id', $request->input('indices'))
                ->delete();

            $this->outletRepository
                ->whereIn('id', $request->input('indices'))
                ->delete();

            return new JsonResponse([
                'message' => trans('pos::app.admin.users.outlets.index.datagrid.mass-delete-success'),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign Products
     *
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function assignProduct($id)
    {
        if (request()->ajax()) {
            return datagrid(OutletProductDataGrid::class)->process();
        }

        $posOutlet = $this->outletRepository->findOrFail($id);

        return view('pos::admin.users.outlets.assign')
            ->with('posOutlet', $posOutlet);
    }

    /**
     * Mass Assign
     */
    public function massAssign(MassUpdateRequest $request, $id): JsonResponse
    {
        $productIds = $request->input('indices');

        $status = $request->input('value');

        foreach ($productIds as $productId) {
            if ($status) {
                $this->outletProductRepository->updateOrCreate([
                    'product_id' => $productId,
                    'outlet_id'  => $id,
                ], [
                    'status' => 1,
                ]);
            } else {
                $this->outletProductRepository->deleteWhere([
                    'product_id' => $productId,
                    'outlet_id'  => $id,
                ]);
            }
        }

        return new JsonResponse([
            'message' => trans('pos::app.admin.users.outlets.assign.datagrid.mass-assign-success'),
        ], 200);
    }
}
