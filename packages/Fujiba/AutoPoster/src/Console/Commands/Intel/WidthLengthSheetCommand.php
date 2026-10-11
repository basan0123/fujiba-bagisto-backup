<?php

namespace Fujiba\AutoPoster\Console\Commands\Intel;

use Fujiba\AutoPoster\Services\Intel\CsvSheet;
use Fujiba\AutoPoster\Services\Intel\PublicCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Owner measuring sheet: every product in fi_product_staging_attributes plus
 * every product whose CURRENT price (active special price, else price) equals
 * --price (default ¥1,595, the lungi-type 2 m piece price). Width/length/notes
 * are left empty for the owner; fujiba:intel:width-length-import reads it back.
 *
 * Supplier codes never go into the public columns: such names/SKUs are masked
 * and the raw value is kept only in the column marked internal_only_original.
 */
class WidthLengthSheetCommand extends Command
{
    protected $signature = 'fujiba:intel:width-length-sheet
        {--tenant=1bd1181f-fcd5-4238-a12d-a207d6ea4006}
        {--price=1595 : include every product at this current price}
        {--csv= : output CSV (default storage/app/intel/fill-in/width_length_fill_in.csv)}
        {--dry-run : count only, write nothing}';

    protected $description = 'Write the owner width/length fill-in sheet (staging products + all products at the given price)';

    public const CSV_HEADER = [
        'product_id', 'sku', 'public_name', 'enabled', 'available_qty', 'price', 'width_cm', 'length_m', 'notes',
        'name_has_supplier_code', 'list_source', 'internal_only_original',
    ];

    public function handle(): int
    {
        if (function_exists('tenancy') && ! tenancy()->initialized) {
            tenancy()->initialize($this->option('tenant'));
        }
        $price = round((float) $this->option('price'), 2);
        $today = now()->toDateString();

        $staging = DB::table('fi_product_staging_attributes')->where('source', '<>', 'owner_fill_in')
            ->distinct()->pluck('product_id')->map(fn ($id) => (int) $id)->all();

        $defaultLocale = DB::table('channels as c')->join('locales as l', 'l.id', '=', 'c.default_locale_id')
            ->selectRaw('c.code AS channel_code, l.code AS locale_code')->pluck('locale_code', 'channel_code')->all();
        $flat = [];
        foreach (DB::table('product_flat')->orderBy('id')
            ->get(['product_id', 'sku', 'name', 'status', 'price', 'special_price', 'special_price_from', 'special_price_to', 'channel', 'locale']) as $r) {
            if (($defaultLocale[$r->channel] ?? 'ja') === $r->locale && ! isset($flat[$r->product_id])) {
                $flat[(int) $r->product_id] = $r;
            }
        }

        $current = function ($r) use ($today) {
            $sp = (float) ($r->special_price ?? 0);
            $active = $sp > 0 && (! $r->special_price_from || $r->special_price_from <= $today) && (! $r->special_price_to || $r->special_price_to >= $today);

            return round($active ? $sp : (float) $r->price, 2);
        };
        $atPrice = array_keys(array_filter($flat, fn ($r) => $current($r) === $price));

        $ids = array_values(array_unique(array_merge($staging, $atPrice)));
        $stock = DB::table('product_inventories')->whereIn('product_id', $ids)->groupBy('product_id')->selectRaw('product_id, SUM(qty) q')->pluck('q', 'product_id');
        $res = DB::table('product_ordered_inventories')->whereIn('product_id', $ids)->groupBy('product_id')->selectRaw('product_id, SUM(qty) q')->pluck('q', 'product_id');

        $rows = [];
        foreach ($ids as $pid) {
            $f = $flat[$pid] ?? null;
            $name = (string) ($f->name ?? '');
            $sku = (string) ($f->sku ?? DB::table('products')->where('id', $pid)->value('sku'));
            $nameSup = PublicCode::hasSupplierCode($name);
            $skuSup = PublicCode::hasSupplierCode($sku);
            $orig = array_filter([$nameSup ? "name={$name}" : null, $skuSup ? "sku={$sku}" : null]);
            $rows[] = [
                'product_id'             => $pid,
                'sku'                    => PublicCode::mask($sku),
                'public_name'            => PublicCode::mask($name),
                'enabled'                => (int) ($f->status ?? 0) === 1 ? 'Y' : 'N',
                'available_qty'          => (int) ($stock[$pid] ?? 0) - (int) ($res[$pid] ?? 0),
                'price'                  => $f ? $current($f) : '',
                'width_cm'               => '',
                'length_m'               => '',
                'notes'                  => '',
                'name_has_supplier_code' => $nameSup ? 'Y' : 'N',
                'list_source'            => implode('+', array_filter([in_array($pid, $staging, true) ? 'staging' : null, in_array($pid, $atPrice, true) ? 'price_'.(int) $price : null])),
                'internal_only_original' => implode('; ', $orig),
            ];
        }
        // in-stock first (measurable now), then by name
        usort($rows, fn ($a, $b) => (($b['available_qty'] > 0) <=> ($a['available_qty'] > 0))
            ?: strnatcasecmp($a['public_name'], $b['public_name']) ?: $a['product_id'] <=> $b['product_id']);

        $this->info(sprintf('staging products: %d | current price ¥%s: %d | union (sheet rows): %d | in stock: %d | supplier-code names (masked): %d',
            count($staging), number_format($price), count($atPrice), count($rows),
            count(array_filter($rows, fn ($r) => $r['available_qty'] > 0)),
            count(array_filter($rows, fn ($r) => $r['name_has_supplier_code'] === 'Y'))));

        if ($this->option('dry-run')) {
            $this->warn('dry-run: nothing written');

            return self::SUCCESS;
        }

        $csv = $this->option('csv') ?: storage_path('app/intel/fill-in/width_length_fill_in.csv');
        $n = CsvSheet::write($csv, self::CSV_HEADER, $rows);
        clearstatcache(true, $csv);
        $this->info(sprintf('wrote %s (%d rows, %d bytes)', $csv, $n, filesize($csv)));

        return self::SUCCESS;
    }
}
