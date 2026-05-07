<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Pos\DataGrids\Admin\BankDataGrid;
use Webkul\Pos\Http\Requests\BankForm;
use Webkul\Pos\Repositories\BankRepository;
use Webkul\Pos\Repositories\UserRepository;

class BanksController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected UserRepository $userRepository,
        protected BankRepository $bankRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(BankDataGrid::class)->process();
        }

        return view('pos::admin.banks.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $agents = $this->userRepository->all();

        return view('pos::admin.banks.create')
            ->with('agents', $agents);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BankForm $request)
    {
        $this->bankRepository->create($request->validated());

        return to_route('admin.pos.banks.index')
            ->with('success', trans('pos::app.admin.banks.create-success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $posBank = $this->bankRepository->findOrFail($id);

        $agents = $this->userRepository->all();

        return view('pos::admin.banks.edit')
            ->with([
                'posBank' => $posBank,
                'agents'  => $agents,
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(BankForm $request, int $id)
    {
        $this->bankRepository->update($request->validated(), $id);

        return to_route('admin.pos.banks.index')
            ->with('success', trans('pos::app.admin.banks.update-success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->bankRepository->delete($id);

            return new JsonResponse([
                'message' => trans('pos::app.admin.banks.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('pos::app.admin.banks.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass Delete the outlets
     */
    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        try {
            $this->bankRepository->whereIn('id', $request->input('indices'))->delete();

            return new JsonResponse([
                'message' => trans('pos::app.admin.banks.index.datagrid.mass-delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
