<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Pos\DataGrids\Admin\UserDataGrid;
use Webkul\Pos\Http\Requests\UserForm;
use Webkul\Pos\Mail\NewRegistrationNotification;
use Webkul\Pos\Repositories\OutletRepository;
use Webkul\Pos\Repositories\UserRepository;

class UsersController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OutletRepository $outletRepository,
        protected UserRepository $userRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(UserDataGrid::class)->process();
        }

        return view('pos::admin.users.users.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $outlets = $this->outletRepository->all();

        return view('pos::admin.users.users.create')
            ->with('outlets', $outlets);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(UserForm $request)
    {
        $data = $request->validated();

        $posUser = $this->userRepository->create(Arr::except($data, 'image'));

        if (request()->hasFile('image')) {
            $posUser->image = current(request()->file('image'))->store('pos/'.$posUser->id);

            $posUser->save();
        }

        try {
            Mail::send(new NewRegistrationNotification($posUser));
        } catch (\Exception $e) {
        }

        return to_route('admin.pos.users.index')
            ->with('success', trans('pos::app.admin.users.users.create-success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $posUser = $this->userRepository->findOrFail($id);

        $outlets = $this->outletRepository->all();

        return view('pos::admin.users.users.edit')
            ->with([
                'posUser' => $posUser,
                'outlets' => $outlets,
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UserForm $request, int $id)
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $posUser = $this->userRepository->update(Arr::except($data, 'image'), $id);

        if (request()->hasFile('image')) {
            $posUser->image = current(request()->file('image'))->store("pos/{$posUser->id}");
        } else {
            if (! request()->has('image.image')) {
                if (! empty(request()->input('image.image'))) {
                    Storage::delete($posUser->image);
                }

                $posUser->image = null;
            }
        }

        $posUser->save();

        return to_route('admin.pos.users.index')
            ->with('success', trans('pos::app.admin.users.users.update-success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userRepository->delete($id);

            return new JsonResponse([
                'message' => trans('pos::app.admin.users.users.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('pos::app.admin.users.users.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass update the users
     */
    public function massUpdate(MassUpdateRequest $request): JsonResponse
    {
        $this->userRepository
            ->whereIn('id', $request->input('indices'))
            ->update(['status' => $request->input('value')]);

        return new JsonResponse([
            'message' => trans('pos::app.admin.users.users.index.datagrid.mass-update-success'),
        ]);
    }

    /**
     * Mass Delete the users
     */
    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        try {
            $this->userRepository->whereIn('id', $request->input('indices'))->delete();

            return new JsonResponse([
                'message' => trans('pos::app.admin.users.users.index.datagrid.mass-delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
