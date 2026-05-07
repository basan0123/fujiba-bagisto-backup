<?php

namespace Fujiba\Dashboard\Http\Controllers\Admin\Concerns;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Request;

trait ResolvesDashboardPeriod
{
    protected function resolvePeriod(Request $request): array
    {
        $defaultStart = Carbon::now()->startOfMonth();
        $defaultEnd   = Carbon::now()->endOfDay();

        $start = $this->parseDateOrFallback($request->query('start'), $defaultStart);
        $end   = $this->parseDateOrFallback($request->query('end'),   $defaultEnd);

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->startOfDay(), $end->endOfDay()];
    }

    protected function parseDateOrFallback(?string $raw, Carbon $fallback): Carbon
    {
        if ($raw === null || $raw === '') {
            return $fallback->copy();
        }

        try {
            return Carbon::parse($raw);
        } catch (InvalidFormatException $e) {
            return $fallback->copy();
        }
    }
}
