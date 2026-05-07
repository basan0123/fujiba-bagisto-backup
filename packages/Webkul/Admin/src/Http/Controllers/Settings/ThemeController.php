<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\DataGrids\Theme\ThemeDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Theme\Repositories\ThemeCustomizationRepository;

class ThemeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(public ThemeCustomizationRepository $themeCustomizationRepository) {}

    /**
     * Display a listing resource for the available tax rates.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ThemeDataGrid::class)->process();
        }

        return view('admin::settings.themes.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function store()
    {
        if (request()->has('id')) {
            $this->validate(request(), [
                core()->getRequestedLocaleCode().'.options.*.image' => 'image|extensions:jpeg,jpg,png,svg,webp',
            ]);

            $theme = $this->themeCustomizationRepository->find(request()->input('id'));

            return $this->themeCustomizationRepository->uploadImage(request()->all(), $theme);
        }

        $validated = $this->validate(request(), [
            'name' => 'required',
            'sort_order' => 'required|numeric',
            'type' => 'required|in:product_carousel,category_carousel,static_content,image_carousel,footer_links,services_content,product_grid_filter',
            'channel_id' => 'required|in:'.implode(',', (core()->getAllChannels()->pluck('id')->toArray())),
            'theme_code' => 'required',
        ]);

        Event::dispatch('theme_customization.create.before');

        $theme = $this->themeCustomizationRepository->create($validated);

        Event::dispatch('theme_customization.create.after', $theme);

        return new JsonResponse([
            'redirect_url' => route('admin.settings.themes.edit', $theme->id),
        ]);
    }

    /**
     * Edit the theme
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $theme = $this->themeCustomizationRepository->find($id);

        return view('admin::settings.themes.edit', compact('theme'));
    }

    /**
     * Update the specified resource
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(int $id)
    {
        $this->validate(request(), [
            'name' => 'required',
            'sort_order' => 'required|numeric',
            'type' => 'required|in:product_carousel,category_carousel,static_content,image_carousel,footer_links,services_content,product_grid_filter',
            'channel_id' => 'required|in:'.implode(',', (core()->getAllChannels()->pluck('id')->toArray())),
            'theme_code' => 'required',
        ]);

        $locale = request('locale');

        $data = request()->only(
            'locale',
            'type',
            'name',
            'sort_order',
            'channel_id',
            'theme_code',
            'status',
            $locale
        );

        Event::dispatch('theme_customization.update.before', $id);

        $data['status'] = request()->input('status') == 'on';

        $theme = $this->themeCustomizationRepository->update($data, $id);

        Event::dispatch('theme_customization.update.after', $theme);

        session()->flash('success', trans('admin::app.settings.themes.update-success'));

        return redirect()->route('admin.settings.themes.index');
    }

    /**
     * Delete a specified theme.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        Event::dispatch('theme_customization.delete.before', $id);

        $this->themeCustomizationRepository->delete($id);

        Storage::deleteDirectory('theme/'.$id);

        Event::dispatch('theme_customization.delete.after', $id);

        return new JsonResponse([
            'message' => trans('admin::app.settings.themes.delete-success'),
        ], 200);
    }

    /**
     * Copy a theme's options from one locale to another so the user can start
     * from the default-locale content and edit it for the target locale.
     * Creates the target translation row if missing; overwrites if present.
     */
    public function copyLocale(int $id): JsonResponse
    {
        $data = $this->validate(request(), [
            'from'      => 'required|string',
            'to'        => 'required|string|different:from',
            'overwrite' => 'sometimes|boolean',
        ]);

        $theme = $this->themeCustomizationRepository->find($id);

        $source = $theme->translate($data['from']);
        if (! $source || empty($source->options)) {
            return new JsonResponse([
                'message' => 'Source locale has no content to copy.',
            ], 422);
        }

        $target = $theme->translateOrNew($data['to']);
        $hasExisting = ! empty($target->options);

        if ($hasExisting && empty($data['overwrite'])) {
            return new JsonResponse([
                'message'        => 'Target locale already has content.',
                'requires_confirm' => true,
            ], 409);
        }

        $target->options = $source->options;
        $target->save();

        return new JsonResponse([
            'message' => trans('admin::app.settings.themes.update-success'),
        ]);
    }

    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $selectedThemeIds = $massUpdateRequest->input('indices');

        $this->themeCustomizationRepository->massUpdateStatus([
            'status' => $massUpdateRequest->input('value'),
        ], $selectedThemeIds);

        return new JsonResponse([
            'message' => trans('admin::app.settings.themes.update-success'),
        ]);
    }

    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $selectedThemeIds = $massDestroyRequest->input('indices');

        foreach ($selectedThemeIds as $themeId) {
            $this->themeCustomizationRepository->delete($themeId);
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.themes.update-success'),
        ]);
    }
}
