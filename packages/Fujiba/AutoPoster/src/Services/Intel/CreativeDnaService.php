<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * J — creative DNA.
 *
 * Components are stored apart from finished assets so the system can learn
 * which *ingredients* earn money, not merely which finished post did well, and
 * can then recombine them into concepts it has never posted before.
 *
 * Fabric is never a component: it is always source_fabric:<product_id>, taken
 * from the real photograph. Nothing here can propose generating it.
 */
class CreativeDnaService
{
    /** Don't repeat an exact combination inside this window. */
    public const REPEAT_WINDOW_DAYS = 30;

    /** Share of picks drawn from proven components; the rest explore. */
    public const EXPLOIT_SHARE = 0.70;

    /** No single component may dominate more than this share of recent use. */
    public const MAX_COMPONENT_SHARE = 0.40;

    public function addComponent(string $type, array $content, array $opts = []): int
    {
        return DB::table('fi_creative_components')->insertGetId([
            'type'       => $type,
            'language'   => $opts['language'] ?? null,
            'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
            'tags'       => $opts['tags'] ?? null,
            'created_by' => $opts['created_by'] ?? 'ai',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function linkToCreative(int $creativeId, array $componentIds): void
    {
        foreach (array_values($componentIds) as $pos => $componentId) {
            DB::table('fi_creative_component_links')->updateOrInsert(
                ['creative_id' => $creativeId, 'component_id' => $componentId],
                ['position' => $pos, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
            );
        }

        DB::table('fi_creative_components')->whereIn('id', $componentIds)->update([
            'last_used_at' => Carbon::now(),
            'updated_at'   => Carbon::now(),
        ]);
        DB::table('fi_creative_components')->whereIn('id', $componentIds)->increment('uses');
    }

    /**
     * Fold a creative's measured outcome back into each component it used.
     *
     * Attribution is deliberately simple and documented: every component in a
     * creative receives the creative's full outcome. That over-credits a
     * component that rode along with a strong one, so comparisons are only
     * meaningful across many creatives — which is why `uses` is reported
     * alongside any rate.
     */
    public function attributeOutcome(int $creativeId, array $outcome): void
    {
        $ids = DB::table('fi_creative_component_links')
            ->where('creative_id', $creativeId)->pluck('component_id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('fi_creative_components')->whereIn('id', $ids)->update([
            'total_clicks'  => DB::raw('total_clicks + '.(int) ($outcome['clicks'] ?? 0)),
            'total_orders'  => DB::raw('total_orders + '.(int) ($outcome['orders'] ?? 0)),
            'total_revenue' => DB::raw('total_revenue + '.(float) ($outcome['revenue'] ?? 0)),
            'updated_at'    => Carbon::now(),
        ]);
    }

    /**
     * Build a new concept by picking one component per requested slot.
     *
     * @param  array<int,string>  $types  e.g. ['hook','scene','character','cta']
     * @return array{components:array<string,int>,combo_hash:string,novel:bool,notes:array}
     */
    public function recombine(array $types, array $opts = []): array
    {
        $notes = [];
        $picked = [];
        $recentShare = $this->recentUsageShare();

        foreach ($types as $type) {
            $pool = DB::table('fi_creative_components')
                ->where('type', $type)
                ->when($opts['language'] ?? null, fn ($q, $l) => $q->where(fn ($w) => $w->where('language', $l)->orWhereNull('language')))
                ->get();

            if ($pool->isEmpty()) {
                $notes[] = "no component available for type {$type}";
                continue;
            }

            // Cap any single component's share of recent use, so the feed does
            // not collapse onto one winning hook.
            $eligible = $pool->reject(fn ($c) => ($recentShare[$c->id] ?? 0) > self::MAX_COMPONENT_SHARE);
            if ($eligible->isEmpty()) {
                $eligible = $pool;
                $notes[] = "share cap relaxed for {$type}: every option is over-used";
            }

            $exploit = (mt_rand() / mt_getrandmax()) < self::EXPLOIT_SHARE;

            if ($exploit) {
                // Proven = highest orders-per-use, requiring at least one use.
                $proven = $eligible->filter(fn ($c) => $c->uses > 0)
                    ->sortByDesc(fn ($c) => $c->total_orders / max(1, $c->uses));
                $choice = $proven->first() ?? $eligible->random();
                if ($proven->isEmpty()) {
                    $notes[] = "{$type}: no proven option yet, explored instead";
                }
            } else {
                // Explore: prefer the least-used, newest ideas.
                $choice = $eligible->sortBy('uses')->first();
            }

            $picked[$type] = (int) $choice->id;
        }

        $hash = $this->comboHash($picked);
        $novel = ! $this->comboUsedRecently($hash);

        if (! $novel) {
            $notes[] = 'this exact combination was used within the last '.self::REPEAT_WINDOW_DAYS.' days';
        }

        return ['components' => $picked, 'combo_hash' => $hash, 'novel' => $novel, 'notes' => $notes];
    }

    public function comboHash(array $componentIds): string
    {
        $ids = array_values($componentIds);
        sort($ids);

        return hash('sha256', implode('-', $ids));
    }

    protected function comboUsedRecently(string $hash): bool
    {
        $since = Carbon::now()->subDays(self::REPEAT_WINDOW_DAYS);

        $rows = DB::table('fi_creatives as c')
            ->join('fi_creative_component_links as l', 'l.creative_id', '=', 'c.id')
            ->where('c.created_at', '>=', $since)
            ->selectRaw('c.id, GROUP_CONCAT(l.component_id ORDER BY l.component_id) ids')
            ->groupBy('c.id')
            ->pluck('ids');

        foreach ($rows as $ids) {
            $list = array_map('intval', explode(',', (string) $ids));
            sort($list);
            if (hash('sha256', implode('-', $list)) === $hash) {
                return true;
            }
        }

        return false;
    }

    /** Each component's share of component-uses in the recent window. */
    protected function recentUsageShare(): array
    {
        $since = Carbon::now()->subDays(self::REPEAT_WINDOW_DAYS);

        $counts = DB::table('fi_creative_component_links as l')
            ->join('fi_creatives as c', 'c.id', '=', 'l.creative_id')
            ->where('c.created_at', '>=', $since)
            ->selectRaw('l.component_id, COUNT(*) n')
            ->groupBy('l.component_id')
            ->pluck('n', 'component_id');

        $total = max(1, (int) $counts->sum());

        return $counts->map(fn ($n) => $n / $total)->all();
    }

    /** Fabric is never generated — this is the only legal fabric reference. */
    public function fabricReference(int $productId): string
    {
        return 'source_fabric:'.$productId;
    }
}
