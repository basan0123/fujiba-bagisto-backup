<?php

namespace Fujiba\SupplierFinder\Services;

use Fujiba\SupplierFinder\Adapters\ShopAdapter;
use Fujiba\SupplierFinder\Models\CrawledProduct;
use Fujiba\SupplierFinder\Models\SourceFabric;

/**
 * Rebuilds sf_source_fabrics from live crawled products. Same decoded key in
 * 2+ shops = same physical fabric. Manual decodes (admin-set maker) are kept.
 */
class Grouper
{
    public function __construct(private CodeDecoder $decoder, private array $config) {}

    public function rebuild(): array
    {
        $this->decoder->flush();
        $groups = [];

        foreach (CrawledProduct::with('shop')->whereNull('gone_at')->get() as $p) {
            if (str_contains($p->title, '<')) {
                $p->update(['title' => trim(preg_replace('/\s+/u', ' ', strip_tags(str_ireplace('<br>', ' ', $p->title))))]);
            }
            // stored codes + codes re-read from the title, so extractor fixes apply without a recrawl
            $codes = array_values(array_unique([...($p->codes ?? []), ...ShopAdapter::codes($p->title)]));
            $dec = $this->decoder->decode($codes, $p->shop->strip_prefixes ?? []);
            $key = $dec['key'] ?? "item:{$p->shop->code}:{$p->external_id}";
            $groups[$key]['dec'] ??= $dec;
            $groups[$key]['items'][] = $p;
        }

        $keep = [];
        foreach ($groups as $key => $g) {
            $items = collect($g['items']);
            $rule = $g['dec']['rule'] ?? null;
            $shops = $items->pluck('shop_id')->unique()->count();
            $fabric = SourceFabric::firstOrNew(['base_code' => mb_substr($key, 0, 80)]);
            $cheapest = $items->whereNotNull('price_per_m')->sortBy('price_per_m')->first();

            if ($fabric->decode_method !== 'manual') {
                [$method, $conf] = match (true) {
                    (bool) $rule  => ['rule', min(1.0, $rule->confidence + ($shops > 1 ? 0.1 : 0))],
                    $shops > 1    => ['cross_shop', 0.5],
                    default       => ['none', 0.2],
                };
                $fabric->fill([
                    'decode_method' => $method, 'confidence' => $conf, 'code_rule_id' => $rule?->id,
                    'supplier_id'   => $rule?->supplier_id ?? ($method === 'none' && $items->first()->shop->supplier_id ? $items->first()->shop->supplier_id : null),
                ]);
            }
            $fabric->fill([
                'family'             => $g['dec']['family'] ?? null,
                'series'             => $rule?->series ?? $items->pluck('series')->filter()->first(),
                'title'              => mb_substr($items->first()->title, 0, 250),
                'material'           => $items->pluck('material')->filter()->first(),
                'width_cm'           => $items->pluck('width_cm')->filter()->first(),
                'lowest_price_per_m' => $cheapest?->price_per_m,
                'lowest_price_type'  => $cheapest?->price_type,
                'shop_count'         => $shops,
            ])->save();

            CrawledProduct::whereIn('id', $items->pluck('id'))->update(['source_fabric_id' => $fabric->id]);
            $keep[] = $fabric->id;
        }

        // orphans with no admin input go; ones with notes/contact/manual decode stay
        SourceFabric::whereNotIn('id', $keep)->where('decode_method', '!=', 'manual')
            ->whereNull('note')->whereNull('contacted_at')->whereNull('draft_product_id')->delete();

        return ['fabrics' => count($keep), 'multi_shop' => collect($groups)->filter(fn ($g) => collect($g['items'])->pluck('shop_id')->unique()->count() > 1)->count()];
    }
}
