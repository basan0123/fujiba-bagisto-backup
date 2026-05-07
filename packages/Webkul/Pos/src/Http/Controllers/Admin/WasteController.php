<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Pos\DataGrids\Admin\WasteEntryDataGrid;
use Webkul\Pos\Repositories\WasteEntryRepository;

class WasteController extends Controller
{
    public function __construct(protected WasteEntryRepository $wasteEntryRepository) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(WasteEntryDataGrid::class)->process();
        }

        return view('pos::admin.waste.index');
    }

    public function show(int $id)
    {
        $entry = $this->wasteEntryRepository->findOrFail($id);

        return view('pos::admin.waste.verify', compact('entry'));
    }

    /**
     * Verify (approve) a waste entry.
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $entry = $this->wasteEntryRepository->findOrFail($id);

        $entry->update([
            'status'      => $request->input('status', 'verified'),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return new JsonResponse([
            'message' => trans('pos::app.admin.waste.verify-success'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->wasteEntryRepository->delete($id);

            return new JsonResponse([
                'message' => trans('pos::app.admin.waste.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        try {
            $this->wasteEntryRepository->whereIn('id', $request->input('indices'))->delete();

            return new JsonResponse([
                'message' => trans('pos::app.admin.waste.mass-delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }
    }
}
