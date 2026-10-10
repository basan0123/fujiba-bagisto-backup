<?php

namespace Fujiba\SupplierFinder\Services;

use Fujiba\SupplierFinder\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Our kimono products (channel 1) with a supplier code = the reference set.
 * Supplier code comes from hidden attr original_product_name, falling back to
 * the public alias name (FV-44-B → 1750-44-B via supplier-image-sync aliases).
 * Image features are cached on disk keyed by path+mtime.
 */
class ReferenceCatalog
{
    private const EN_MATERIAL = ['hosonuno' => '細布', 'square' => 'スケアー', 'sheeting' => 'シーチング', 'broad' => 'ブロード',
        'chirimen' => 'ちりめん', 'crepe' => 'ちりめん', 'twill' => 'ツイル', 'satin' => 'サテン', 'oxford' => 'オックス'];

    private const EN_STYLE = ['kaika' => '開花', 'folk' => '民芸', 'chiyogami' => '千代紙', 'yuzen' => '友禅', 'cherry' => '桜',
        'traditional' => '和柄', 'bingata' => '紅型', 'crane' => '鶴', 'temari' => '手毬', 'peony' => '牡丹', 'gold' => '金彩'];

    public function __construct(private CodeDecoder $decoder) {}

    public function load(): array
    {
        $channel = (int) config('supplier-finder.channel_id');
        $attr = fn ($code) => DB::table('attributes')->where('code', $code)->value('id');
        $opnId = $attr('original_product_name');
        $supId = $attr('supplier');
        $costId = $attr('cost');

        $rows = DB::select("select pf.product_id, pf.sku, pf.name, pf.price, pf.status, pf.description,
                (select text_value from product_attribute_values v where v.product_id=pf.product_id and v.attribute_id=? limit 1) opn,
                (select text_value from product_attribute_values v where v.product_id=pf.product_id and v.attribute_id=? limit 1) supplier,
                (select float_value from product_attribute_values v where v.product_id=pf.product_id and v.attribute_id=? limit 1) cost,
                (select path from product_images i where i.product_id=pf.product_id order by position limit 1) img
            from product_flat pf join product_channels pc on pc.product_id=pf.product_id and pc.channel_id=?
            where pf.channel='default' and pf.locale='ja'", [$opnId, $supId, $costId, $channel]);

        $aliases = config('supplier-image-sync.name_aliases', []);
        $imgRoot = storage_path('app/public/tenants/'.$channel.'/'.config('supplier-finder.tenant_id'));
        $cacheFile = TenantContext::dir().'/ref-features.json';
        $cache = is_file($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : [];
        $out = [];

        foreach ($rows as $r) {
            $code = $this->supplierCode((string) $r->opn, (string) $r->name, $aliases);
            if (! $code) {
                continue;
            }
            $dec = $this->decoder->decode([$code]);
            $desc = strtolower(strip_tags((string) $r->description).' '.$r->name);
            $features = null;
            if ($r->img) {
                $path = $this->bestImage($imgRoot.'/'.$r->img);
                $key = $path && is_file($path) ? $path.'@'.filemtime($path) : null;
                if ($key) {
                    $features = $cache[$key] ??= ImageFeatures::fromFile($path);
                }
            }
            $out[] = (object) [
                'product_id' => $r->product_id, 'sku' => $r->sku, 'name' => $r->name, 'status' => (int) $r->status,
                'price' => (float) $r->price, 'cost' => $r->cost ? (float) $r->cost : null, 'supplier' => $r->supplier,
                'code' => $code, 'design_key' => $dec['key'] ?? null,
                'material' => $this->firstMatch($desc, self::EN_MATERIAL) ?? (str_contains($desc, 'ちりめん') ? 'ちりめん' : null),
                'style' => array_values(array_unique(array_filter(array_map(fn ($k) => str_contains($desc, $k) ? self::EN_STYLE[$k] : null, array_keys(self::EN_STYLE))))),
                'features' => $features, 'image' => $r->img,
            ];
        }
        file_put_contents($cacheFile, json_encode($cache));

        return $out;
    }

    private function supplierCode(string $opn, string $name, array $aliases): ?string
    {
        $opn = trim($opn);
        $c = $opn !== '' && preg_match('/\d{3}/', $opn) ? $opn : $name;
        $c = preg_replace('/\s*\(.*$|[^\x20-\x7E].*$/u', '', $c);   // drop "(3m)…", Burmese/JP suffixes
        $c = strtoupper(str_replace('_', '-', trim($c)));
        $c = preg_replace('/^B-(1000)/', 'B$1', $c);
        if (preg_match('/^(F[A-Z])-?(.+)$/', $c, $m) && isset($aliases[$m[1]])) {
            $c = $aliases[$m[1]] === 'AP' ? 'AP'.$m[2] : $aliases[$m[1]].'-'.$m[2];
        }

        return preg_match('/\d{3}/', $c) ? $c : null;
    }

    private function bestImage(string $path): ?string
    {
        // prefer the .jpg sibling of a .webp (GD webp support varies)
        $jpg = preg_replace('/\.webp$/', '.jpg', $path);

        return is_file($jpg) ? $jpg : (is_file($path) ? $path : null);
    }

    private function firstMatch(string $text, array $map): ?string
    {
        foreach ($map as $k => $v) {
            if (str_contains($text, $k)) {
                return $v;
            }
        }

        return null;
    }
}
