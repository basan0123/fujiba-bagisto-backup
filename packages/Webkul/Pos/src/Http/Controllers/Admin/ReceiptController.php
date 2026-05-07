<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Pos\DataGrids\Admin\ReceiptDataGrid;
use Webkul\Pos\Http\Requests\ReceiptForm;
use Webkul\Pos\Repositories\ReceiptRepository;

class ReceiptController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected ReceiptRepository $receiptRepository) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ReceiptDataGrid::class)->process();
        }

        return view('pos::admin.receipts.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('pos::admin.receipts.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ReceiptForm $request)
    {
        $receipt = $this->receiptRepository->create(Arr::except($request->validated(), ['logo']));

        if ($request->hasFile('logo')) {
            $receipt->logo = current($request->file('logo'))->store("pos-receipts/{$receipt->id}");

            $receipt->save();
        }

        return to_route('admin.pos.receipts.index')
            ->with('success', trans('pos::app.admin.receipts.create-success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $receipt = $this->receiptRepository->findOrFail($id);

        return view('pos::admin.receipts.edit')
            ->with('receipt', $receipt);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function show(int $id)
    {
        $posReceipt = $this->receiptRepository
            ->with([
                'outlet' => [
                    'pos_user',
                ],
            ])
            ->findOrFail($id);

        return view('pos::admin.receipts.preview')
            ->with('posReceipt', $posReceipt);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ReceiptForm $request, int $id)
    {
        $receipt = $this->receiptRepository->update(Arr::except($request->validated(), ['logo']), $id);

        if ($request->hasFile('logo')) {
            if ($receipt->logo) {
                Storage::delete($receipt->logo);
            }

            $receipt->logo = current($request->file('logo'))->store("pos-receipts/{$receipt->id}");
        } else {
            if (! $request->has('logo.logo')) {
                if (! empty($request->input('logo.logo'))) {
                    Storage::delete($receipt->logo);
                }

                $receipt->logo = null;
            }
        }

        $receipt->save();

        return to_route('admin.pos.receipts.index')
            ->with('success', trans('pos::app.admin.receipts.update-success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->receiptRepository->delete($id);

            return new JsonResponse([
                'message' => trans('pos::app.admin.receipts.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the resources from storage.
     */
    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        try {
            $this->receiptRepository->whereIn('id', $request->input('indices'))->delete();

            return new JsonResponse([
                'message' => trans('pos::app.admin.receipts.index.datagrid.mass-delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
