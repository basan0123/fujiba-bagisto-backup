<?php

namespace Fujiba\AutoPoster\Ops\Insights;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Published posts (current tenant) and their fi_creatives mapping. Read only.
 * Each row: [queue_id, platform('fb'|'ig'), external_id, published_at, creative_id|null]
 */
class PublishedPostSource
{
    public function __construct(private int $lookbackDays = 28) {}

    public function posts(): array
    {
        $since = now()->subDays($this->lookbackDays);
        $out = [];

        $hasIg = Schema::hasColumn('autoposter_queue', 'instagram_media_id');
        $cols = ['id', 'facebook_post_id', 'published_at'];
        if ($hasIg) {
            array_push($cols, 'instagram_media_id', 'instagram_published_at');
        }

        $rows = DB::table('autoposter_queue')
            ->where(function ($q) use ($since, $hasIg) {
                $q->where(fn ($w) => $w->whereNotNull('facebook_post_id')->where('published_at', '>=', $since));
                if ($hasIg) {
                    $q->orWhere(fn ($w) => $w->whereNotNull('instagram_media_id')->where('instagram_published_at', '>=', $since));
                }
            })
            ->get($cols);

        $ids = [];
        foreach ($rows as $r) {
            if ($r->facebook_post_id) {
                $out[] = ['queue_id' => $r->id, 'platform' => 'fb', 'external_id' => $r->facebook_post_id, 'published_at' => $r->published_at];
                $ids[] = $r->facebook_post_id;
            }
            if ($hasIg && $r->instagram_media_id) {
                $out[] = ['queue_id' => $r->id, 'platform' => 'ig', 'external_id' => $r->instagram_media_id, 'published_at' => $r->instagram_published_at];
                $ids[] = $r->instagram_media_id;
            }
        }

        $map = $ids && Schema::hasTable('fi_creatives')
            ? DB::table('fi_creatives')->whereIn('external_post_id', $ids)->pluck('id', 'external_post_id')->all()
            : [];

        foreach ($out as &$p) {
            $p['creative_id'] = $map[$p['external_id']] ?? null;
        }

        return $out;
    }
}
