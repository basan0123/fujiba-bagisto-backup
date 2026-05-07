<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Pos\DataGrids\Admin\ProductRequestDataGrid;
use Webkul\Pos\Repositories\ProductRequestRepository;
use Webkul\Product\Helpers\ProductType;
use Webkul\Product\Repositories\ProductInventoryRepository;

class ProductRequestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductInventoryRepository $productInventoryRepository,
        protected ProductRequestRepository $productRequestRepository,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ProductRequestDataGrid::class)->process();
        }

        return view('pos::admin.requests.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function view(int $id)
    {
        $productRequest = $this->productRequestRepository
            ->with([
                'product',
                'user' => [
                    'outlet' => [
                        'inventory_source',
                    ],
                ],
            ])
            ->findOrFail($id);

        return view('pos::admin.requests.view')
            ->with('productRequest', $productRequest);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(int $id)
    {
        request()->validate([
            'request_status' => 'required|integer',
        ]);

        $this->updateInventory(request()->input('request_status'), $id);

        return to_route('admin.pos.requests.index')
            ->withSuccess(trans('pos::app.admin.requests.update-success'));
    }

    /**
     * Mass Update
     */
    public function massUpdate(MassUpdateRequest $request): JsonResponse
    {
        try {
            foreach ($request->input('indices') as $id) {
                $this->updateInventory($request->input('value'), $id);
            }

            return new JsonResponse([
                'message' => trans('pos::app.admin.requests.index.datagrid.mass-update-success'),
            ]);
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message' => trans('pos::app.admin.requests.index.datagrid.mass-update-error'),
        ], 500);
    }

    /**
     * Update inventory based on request status
     */
    public function updateInventory(int $option, int $id): bool
    {
        $productRequest = $this->productRequestRepository
            ->with([
                'product',
                'user' => [
                    'outlet',
                ],
            ])
            ->findOrFail($id);

        $product = $productRequest->product;

        $productArray = [$product];

        if (ProductType::hasVariants($product->type)) {
            $productArray = $product->variants()->get();
        }

        foreach ($productArray as $product) {
            $productInventory = $this->productInventoryRepository->findOneWhere([
                'product_id'          => $product->id,
                'inventory_source_id' => $productRequest->user->outlet->inventory_source_id,
            ]);

            // Complete Request
            if (
                $option == 1
                && $productRequest->request_status != 1
            ) {
                $productInventory->increment('qty', $productRequest->requested_quantity);

                $productRequest->request_status = 1;
                $productRequest->save();

                $count = 1;
            }

            // Decline Request
            if (
                $option == 2
                && $productRequest->request_status != 1
            ) {
                $productRequest->request_status = 2;
                $productRequest->save();

                $count = 1;
            }

            // Pending Request
            if (
                $option == 0
                && $productRequest->request_status == 2
            ) {
                $productRequest->request_status = 0;
                $productRequest->save();

                $count = 1;
            }
        }

        return $count ? true : false;
    }
}
