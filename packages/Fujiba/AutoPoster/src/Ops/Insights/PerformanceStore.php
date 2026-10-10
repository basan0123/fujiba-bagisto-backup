<?php

namespace Fujiba\AutoPoster\Ops\Insights;

use Illuminate\Support\Facades\DB;

/** Idempotent upsert into fi_performance on (creative_id, date, source). */
class PerformanceStore
{
    public function upsert(int $creativeId, string $date, string $source, array $metrics): void
    {
        $now = now();
        DB::table('fi_performance')->upsert(
            [array_merge($metrics, ['creative_id' => $creativeId, 'date' => $date, 'source' => $source, 'created_at' => $now, 'updated_at' => $now])],
            ['creative_id', 'date', 'source'],
            array_merge(array_keys($metrics), ['updated_at']),
        );
    }
}
