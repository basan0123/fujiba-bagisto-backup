<?php

namespace Fujiba\Accounting\Services\Reports;

use Fujiba\Accounting\Models\FiscalPeriod;
use Illuminate\Support\Carbon;

/**
 * Normalize the three period-input shapes used by reports
 *   - year_month   "2026-04"
 *   - fiscal_year  "2026" (looks up the fujiba_fiscal_periods row whose
 *                          start_date.year == 2026 — Japanese fiscal year)
 *   - from / to    explicit dates
 * into a (Carbon $from, Carbon $to) pair.
 *
 * For B/S we also accept `as_of` and resolve as (epoch, as_of).
 */
class ReportPeriodResolver
{
    /**
     * @return array{from: Carbon, to: Carbon, label: string}
     */
    public function resolve(array $params): array
    {
        if (! empty($params['year_month'])) {
            $ym = (string) $params['year_month'];
            if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
                throw new \InvalidArgumentException("Invalid year_month: {$ym}");
            }
            $from = Carbon::parse("{$ym}-01")->startOfMonth();
            return ['from' => $from, 'to' => $from->copy()->endOfMonth(), 'label' => $ym];
        }

        if (! empty($params['fiscal_year'])) {
            $fy = (int) $params['fiscal_year'];
            $period = FiscalPeriod::query()
                ->whereYear('start_date', $fy)
                ->orderBy('start_date')
                ->first();
            if (! $period) {
                throw new \InvalidArgumentException("No fiscal period for year {$fy}");
            }
            return [
                'from'  => Carbon::parse($period->start_date->toDateString()),
                'to'    => Carbon::parse($period->end_date->toDateString()),
                'label' => $period->name ?: (string) $fy,
            ];
        }

        if (! empty($params['as_of'])) {
            $to = Carbon::parse($params['as_of'])->endOfDay();
            return [
                'from'  => Carbon::create(1970, 1, 1)->startOfDay(),
                'to'    => $to,
                'label' => $to->toDateString(),
            ];
        }

        $from = ! empty($params['from'])
            ? Carbon::parse($params['from'])->startOfDay()
            : Carbon::now()->startOfMonth();
        $to = ! empty($params['to'])
            ? Carbon::parse($params['to'])->endOfDay()
            : Carbon::now()->endOfMonth();

        if ($to->lt($from)) {
            throw new \InvalidArgumentException('to < from');
        }

        return [
            'from'  => $from,
            'to'    => $to,
            'label' => $from->toDateString() . ' → ' . $to->toDateString(),
        ];
    }
}
