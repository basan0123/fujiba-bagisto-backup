<?php

namespace Fujiba\SupplierFinder\Console;

use Fujiba\SupplierFinder\Models\FabricMatch;
use Fujiba\SupplierFinder\Models\SourceFabric;
use Fujiba\SupplierFinder\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportCommand extends Command
{
    protected $signature = 'supplier-finder:report {--similar=30} {--json= : also write the report as JSON to this path}';

    protected $description = 'Decoded groups, EXACT sources and top SIMILAR matches';

    public function handle(): int
    {
        TenantContext::enter();
        $names = DB::table('product_flat')->where('channel', 'default')->where('locale', 'ja')->pluck('name', 'product_id');
        $fabrics = SourceFabric::with(['products.shop', 'supplier'])->get();

        $families = $fabrics->groupBy(fn ($f) => $f->family ?? (str_starts_with($f->base_code, 'item:') ? '(no code)' : 'unknown code'))
            ->map(fn ($g, $fam) => [
                'family' => $fam, 'fabrics' => $g->count(), 'items' => $g->sum(fn ($f) => $f->products->count()),
                'maker' => $g->first()->supplier?->name_en ?? 'maker unknown',
                'shops' => $g->flatMap(fn ($f) => $f->products->pluck('shop.code'))->unique()->values()->all(),
                'cross_shop' => $g->where('shop_count', '>', 1)->count(),
            ])->sortByDesc('items')->values();

        $cross = $fabrics->where('shop_count', '>', 1)->map(fn ($f) => [
            'key' => $f->base_code, 'maker' => $f->supplier?->name_en ?? 'maker unknown', 'method' => $f->decode_method, 'conf' => $f->confidence,
            'title' => $f->title,
            'offers' => $f->products->map(fn ($p) => ['shop' => $p->shop->code, 'yen_m' => $p->price_per_m, 'type' => $p->price_type, 'url' => $p->url])->values()->all(),
        ])->values();

        $exact = FabricMatch::with('crawled.shop', 'crawled.fabric.supplier')->where('match_type', 'exact')->orderByDesc('score')->get()
            ->unique('crawled_product_id')->map(fn ($m) => $this->row($m, $names))->values();

        $similar = FabricMatch::with('crawled.shop', 'crawled.fabric.supplier')->where('match_type', 'similar')
            ->whereNotIn('crawled_product_id', FabricMatch::where('match_type', 'exact')->pluck('crawled_product_id'))
            ->orderByDesc('score')->get()->unique('crawled_product_id')
            ->take((int) $this->option('similar'))->map(fn ($m) => $this->row($m, $names))->values();

        $this->info('Decoded families');
        $this->table(['family', 'maker', 'fabrics', 'items', 'cross-shop', 'shops'], $families->map(fn ($f) => [$f['family'], $f['maker'], $f['fabrics'], $f['items'], $f['cross_shop'], implode(',', $f['shops'])]));
        $this->info('Cross-shop groups');
        $this->table(['key', 'maker', 'offers'], $cross->map(fn ($c) => [$c['key'], $c['maker'], collect($c['offers'])->map(fn ($o) => "{$o['shop']} ¥{$o['yen_m']}/m")->implode(' · ')]));
        $this->info("EXACT ({$exact->count()})");
        $this->table(['shop', 'title', '¥/m', 'ours', 'score'], $exact->map(fn ($r) => [$r['shop'], mb_strimwidth($r['title'], 0, 50, '…'), $r['yen_m'], $r['ours'], $r['score']]));
        $this->info("Top {$similar->count()} SIMILAR");
        $this->table(['shop', 'title', '¥/m', 'maker', 'like ours', 'score', 'img/txt/mat'], $similar->map(fn ($r) => [$r['shop'], mb_strimwidth($r['title'], 0, 46, '…'), $r['yen_m'], $r['maker'], $r['ours'], $r['score'], $r['parts']]));

        if ($path = $this->option('json')) {
            file_put_contents($path, json_encode(compact('families', 'cross', 'exact', 'similar'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        return 0;
    }

    private function row(FabricMatch $m, $names): array
    {
        $c = $m->crawled;

        return [
            'shop' => $c->shop->code, 'title' => $c->title, 'url' => $c->url, 'yen_m' => $c->price_per_m, 'type' => $c->price_type,
            'unit' => $c->price_unit, 'material' => $c->material, 'width' => $c->width_cm,
            'maker' => $c->fabric?->supplier?->name_en ?? ($c->fabric?->family ? $c->fabric->family.' (maker unknown)' : 'unknown'),
            'key' => $c->fabric?->base_code, 'ours' => $names[$m->product_id] ?? $m->product_id, 'ours_code' => $m->details['ref_code'] ?? null,
            'product_id' => $m->product_id, 'score' => $m->score,
            'parts' => sprintf('%.2f/%.2f/%.2f', $m->image_score, $m->text_score, $m->material_score),
            'phash_d' => $m->details['phash_d'] ?? null, 'words' => $m->details['words'] ?? [],
        ];
    }
}
