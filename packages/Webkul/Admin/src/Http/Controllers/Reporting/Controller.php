<?php

namespace Webkul\Admin\Http\Controllers\Reporting;

use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\ReportingExport;
use Webkul\Admin\Helpers\Reporting as ReportingHelper;
use Webkul\Admin\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * Request param functions.
     *
     * @var array
     */
    protected $typeFunctions = [];

    /**
     * How long (seconds) to cache a computed stats payload. Short enough that
     * a new order shows up within a minute, long enough that switching widgets
     * / re-tapping a preset is instant. Set to 0 to disable.
     */
    protected int $cacheTtl = 60;

    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(protected ReportingHelper $reportingHelper) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $fn = $this->resolveTypeFunction();

        $payload = $this->remember(function () use ($fn) {
            return [
                'statistics' => $this->reportingHelper->{$fn}(),
                'date_range' => $this->reportingHelper->getDateRange(),
            ];
        });

        return response()->json($payload);
    }

    /**
     * Cache a stats payload for `$cacheTtl` seconds, keyed on the tenant, the
     * reporting controller, the requested type and every filter param.
     *
     * The cache store is shared across tenants in this install (no
     * CacheTenancyBootstrapper), so the tenant id MUST be part of the key to
     * prevent one store's figures leaking into another's report.
     */
    protected function remember(\Closure $callback): array
    {
        if ($this->cacheTtl <= 0) {
            return $callback();
        }

        $tenantId = function_exists('tenant') && tenant() ? tenant()->getTenantKey() : 'central';

        $params = request()->only(['type', 'start', 'end', 'channel', 'mode', 'period']);
        ksort($params);

        $key = 'reporting:'.$tenantId.':'.static::class.':'.md5(json_encode($params));

        return Cache::remember($key, $this->cacheTtl, $callback);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewStats()
    {
        $stats = $this->reportingHelper->{$this->resolveTypeFunction()}('table');

        return response()->json([
            'statistics' => $stats,
            'date_range' => $this->reportingHelper->getDateRange(),
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export()
    {
        $stats = $this->reportingHelper->{$this->resolveTypeFunction()}('table');

        return Excel::download(new ReportingExport($stats), request()->query('type').'.'.request()->query('format'));
    }

    /**
     * Validate if the requested type is valid.
     *
     * @return void
     */
    protected function validateRequestedType()
    {
        return ! array_key_exists(request()->query('type'), $this->typeFunctions);
    }

    /**
     * Resolve the requested type into a valid function name.
     *
     * @return string
     */
    protected function resolveTypeFunction()
    {
        if ($this->validateRequestedType()) {
            abort(404);
        }

        return $this->typeFunctions[request()->query('type')];
    }
}
