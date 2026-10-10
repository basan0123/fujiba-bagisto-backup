<?php

namespace Fujiba\AutoPoster\Console\Commands\Intel;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Workstream B — catalog inventory + measured fabric facts.
 *
 * Read-only on product tables and image files; writes ONLY fi_catalog_* tables.
 * Resumable: an image is skipped once analysis_status is done/missing for the
 * current analyser version, so re-running continues where it stopped.
 *
 *   php artisan fujiba:intel:catalog --inventory
 *   php artisan fujiba:intel:catalog --analyse --skus=KIMONO-2561,KIMONO-2551
 *   php artisan fujiba:intel:catalog --analyse --all --batch=20
 */
class CatalogCommand extends Command
{
    public const VERSION = 'cat-1.1';

    protected $signature = 'fujiba:intel:catalog
        {--inventory : Rebuild fi_catalog_items/fi_catalog_images from product tables}
        {--analyse : Run measured analysis on pending images}
        {--all : Analyse every pending image (otherwise needs --skus)}
        {--skus= : Comma-separated SKUs to analyse}
        {--batch=20 : Images per python invocation}
        {--max-load=4.2 : Pause while 1-min load average exceeds this}
        {--channel=default}
        {--image-root= : Override tenant image root}';

    protected $description = 'Inventory Fujiba catalog + images and record measured-only fabric facts (fi_catalog_*)';

    public function handle(): int
    {
        if (function_exists('tenancy') && ! tenancy()->initialized) {
            tenancy()->initialize('1bd1181f-fcd5-4238-a12d-a207d6ea4006');
        }

        if ($this->option('inventory')) {
            $this->inventory();
        }

        if ($this->option('analyse')) {
            $this->analyse();
            $this->dedupeNear();
            $this->rollupItems();
        }

        return self::SUCCESS;
    }

    protected function imageRoot(): string
    {
        if ($r = $this->option('image-root')) {
            return rtrim($r, '/');
        }
        $tenant = tenant('id') ?: '1bd1181f-fcd5-4238-a12d-a207d6ea4006';
        foreach ([storage_path('app/public/tenants/1/'.$tenant), '/var/www/bagisto/storage/app/public/tenants/1/'.$tenant] as $p) {
            if (is_dir($p.'/product')) {
                return $p;
            }
        }

        return storage_path('app/public/tenants/1/'.$tenant);
    }

    protected function inventory(): void
    {
        $root = $this->imageRoot();
        $ch = $this->option('channel');
        $now = now();

        $flat = DB::table('product_flat')->where('channel', $ch)->where('locale', 'ja')
            ->get(['product_id', 'sku', 'name', 'type', 'status', 'parent_id', 'visible_individually', 'price'])
            ->keyBy('product_id');
        $ids = $flat->keys()->all();
        $products = DB::table('products')->whereIn('id', $ids)->get(['id', 'sku', 'type', 'parent_id'])->keyBy('id');
        $qty = DB::table('product_inventories')->whereIn('product_id', $ids)
            ->groupBy('product_id')->select('product_id', DB::raw('SUM(qty) q'))->pluck('q', 'product_id');
        $children = DB::table('products')->whereIn('parent_id', $ids)
            ->groupBy('parent_id')->select('parent_id', DB::raw('COUNT(*) c'))->pluck('c', 'parent_id');
        $images = DB::table('product_images')->whereIn('product_id', $ids)->orderBy('product_id')->orderBy('position')->orderBy('id')->get();

        $skuCounts = $flat->groupBy(fn ($r) => mb_strtolower(trim((string) $r->sku)))->map->count();
        $nameCounts = $flat->groupBy(fn ($r) => mb_strtolower(trim((string) $r->name)))->map->count();

        // images
        $byProduct = [];
        $bar = $this->output->createProgressBar(count($images));
        foreach ($images as $img) {
            $abs = $root.'/'.ltrim($img->path, '/');
            $exists = is_file($abs);
            $row = [
                'product_id' => $img->product_id, 'path' => $img->path, 'position' => $img->position,
                'exists_on_disk' => $exists, 'bytes' => $exists ? filesize($abs) : null, 'updated_at' => $now,
            ];
            if ($exists) {
                $sz = @getimagesize($abs);
                $row['width'] = $sz[0] ?? null;
                $row['height'] = $sz[1] ?? null;
                $row['sha256'] = hash_file('sha256', $abs);
            } else {
                $row['analysis_status'] = 'missing';
            }
            $existing = DB::table('fi_catalog_images')->where('image_id', $img->id)->first();
            if ($existing) {
                // file changed -> re-analyse
                if ($exists && $existing->sha256 !== $row['sha256']) {
                    $row['analysis_status'] = 'pending';
                } elseif ($exists && $existing->analysis_status === 'missing') {
                    $row['analysis_status'] = 'pending';
                }
                DB::table('fi_catalog_images')->where('image_id', $img->id)->update($row);
            } else {
                DB::table('fi_catalog_images')->insert($row + ['image_id' => $img->id, 'created_at' => $now]);
            }
            $byProduct[$img->product_id][] = ['id' => $img->id, 'exists' => $exists];
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // stale image rows (image record deleted)
        $gone = DB::table('fi_catalog_images')->whereNotIn('image_id', $images->pluck('id'))->pluck('image_id');
        if ($gone->count()) {
            DB::table('fi_catalog_images')->whereIn('image_id', $gone)->update(['analysis_status' => 'missing', 'error' => 'product_images row no longer exists', 'exists_on_disk' => false]);
        }

        // exact duplicates by sha256
        DB::statement('UPDATE fi_catalog_images i JOIN (SELECT sha256, MIN(image_id) m FROM fi_catalog_images WHERE sha256 IS NOT NULL GROUP BY sha256) d ON d.sha256 = i.sha256 SET i.exact_dup_of = IF(i.image_id = d.m, NULL, d.m)');

        foreach ($flat as $pid => $f) {
            $imgs = $byProduct[$pid] ?? [];
            $on = count(array_filter($imgs, fn ($i) => $i['exists']));
            $primary = collect($imgs)->firstWhere('exists', true)['id'] ?? null;
            $p = $products->get($pid);
            $issues = [];
            if (! $p) {
                $issues[] = 'flat_row_without_products_row';
            }
            if (trim((string) $f->sku) === '') {
                $issues[] = 'empty_sku';
            }
            if (trim((string) $f->name) === '') {
                $issues[] = 'empty_name';
            }
            if (! $imgs) {
                $issues[] = 'no_image_records';
            } elseif ($on < count($imgs)) {
                $issues[] = 'image_file_missing';
            }
            if ($p && $p->sku !== $f->sku) {
                $issues[] = 'sku_mismatch_products_vs_flat';
            }
            if (($skuCounts[mb_strtolower(trim((string) $f->sku))] ?? 1) > 1) {
                $issues[] = 'duplicate_sku';
            }
            if (($nameCounts[mb_strtolower(trim((string) $f->name))] ?? 1) > 1) {
                $issues[] = 'duplicate_name';
            }
            $row = [
                'sku' => $f->sku, 'name' => $f->name, 'type' => $f->type ?? $p?->type, 'status' => $f->status,
                'parent_id' => $f->parent_id ?? $p?->parent_id, 'child_count' => (int) ($children[$pid] ?? 0),
                'visible_individually' => $f->visible_individually, 'price' => $f->price,
                'qty' => isset($qty[$pid]) ? (int) $qty[$pid] : null,
                'image_count' => count($imgs), 'images_on_disk' => $on, 'images_missing' => count($imgs) - $on,
                'primary_image_id' => $primary,
                'dup_sku_count' => (int) ($skuCounts[mb_strtolower(trim((string) $f->sku))] ?? 1),
                'dup_name_count' => (int) ($nameCounts[mb_strtolower(trim((string) $f->name))] ?? 1),
                'issues' => json_encode($issues), 'inventoried_at' => $now, 'updated_at' => $now,
            ];
            DB::table('fi_catalog_items')->updateOrInsert(['product_id' => $pid], $row + ['created_at' => $now]);
        }
        DB::table('fi_catalog_items')->whereNotIn('product_id', $ids)->update(['issues' => json_encode(['no_longer_in_channel']), 'updated_at' => $now]);

        // products sharing an identical image with another product
        DB::statement('UPDATE fi_catalog_items it LEFT JOIN (
              SELECT a.product_id, COUNT(DISTINCT b.product_id) c FROM fi_catalog_images a
              JOIN fi_catalog_images b ON a.sha256 = b.sha256 AND a.product_id <> b.product_id
              WHERE a.sha256 IS NOT NULL GROUP BY a.product_id) x ON x.product_id = it.product_id
            SET it.dup_image_products = COALESCE(x.c, 0)');

        $this->info(sprintf('Inventory: %d products, %d images (%d missing on disk) root=%s',
            $flat->count(), $images->count(), $images->count() - array_sum(array_map(fn ($l) => count(array_filter($l, fn ($i) => $i['exists'])), $byProduct)), $root));
    }

    protected function analyse(): void
    {
        $root = $this->imageRoot();
        $q = DB::table('fi_catalog_images as i')->join('fi_catalog_items as it', 'it.product_id', '=', 'i.product_id')
            ->where('i.exists_on_disk', true)
            ->where(fn ($w) => $w->where('i.analysis_status', 'pending')->orWhere('i.analysis_version', '!=', self::VERSION)->orWhereNull('i.analysis_version'))
            ->whereIn('i.analysis_status', ['pending', 'done', 'error']);
        if ($s = $this->option('skus')) {
            $q->whereIn('it.sku', array_map('trim', explode(',', $s)));
        } elseif (! $this->option('all')) {
            $this->error('Pass --skus=... or --all');

            return;
        }
        // in-stock enabled first
        $todo = $q->orderByRaw('it.status DESC, (COALESCE(it.qty,0) > 0) DESC, i.product_id, i.position')
            ->get(['i.image_id', 'i.product_id', 'i.path']);
        $this->info('Pending images: '.count($todo));
        if (! count($todo)) {
            return;
        }

        $dir = storage_path('app/intel');
        @mkdir($dir, 0775, true);
        $runId = DB::table('fi_catalog_runs')->insertGetId(['mode' => $this->option('all') ? 'all' : 'skus', 'analysis_version' => self::VERSION,
            'pid' => getmypid(), 'status' => 'running', 'started_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $script = base_path('bin/intel/catalog_analyse.py');
        $done = 0;
        $errs = 0;
        $total = count($todo);

        foreach ($todo->chunk(max(1, (int) $this->option('batch'))) as $chunk) {
            while (($load = sys_getloadavg()[0]) > (float) $this->option('max-load')) {
                $this->line(sprintf('[%s] load %.2f > %s — pausing 60s', now()->toTimeString(), $load, $this->option('max-load')));
                DB::table('fi_catalog_runs')->where('id', $runId)->update(['status' => 'paused', 'updated_at' => now()]);
                sleep(60);
            }
            DB::table('fi_catalog_runs')->where('id', $runId)->update(['status' => 'running']);
            $in = $dir.'/catalog_in_'.$runId.'.json';
            $out = $dir.'/catalog_out_'.$runId.'.json';
            file_put_contents($in, json_encode($chunk->map(fn ($r) => ['image_id' => $r->image_id, 'path' => $root.'/'.ltrim($r->path, '/')])->values()));
            @unlink($out);
            exec('nice -n 10 python3 '.escapeshellarg($script).' '.escapeshellarg($in).' '.escapeshellarg($out).' 2>&1', $o, $rc);
            $res = is_file($out) ? json_decode(file_get_contents($out), true) : null;
            if (! is_array($res)) {
                $this->error('python failed rc='.$rc.': '.implode("\n", array_slice($o, -5)));
                $errs += count($chunk);
                DB::table('fi_catalog_runs')->where('id', $runId)->update(['status' => 'failed', 'errors' => $errs, 'note' => 'python failed rc='.$rc, 'finished_at' => now()]);

                return;
            }
            $pids = $chunk->pluck('product_id', 'image_id');
            DB::transaction(function () use ($res, $pids, &$done, &$errs) {
                foreach ($res as $r) {
                    $iid = $r['image_id'];
                    if (! $r['ok']) {
                        $errs++;
                        DB::table('fi_catalog_images')->where('image_id', $iid)->update(['analysis_status' => 'error', 'analysis_version' => self::VERSION, 'error' => mb_substr($r['error'] ?? '?', 0, 1000), 'analysed_at' => now()]);

                        continue;
                    }
                    foreach ($r['facts'] as $fact => $f) {
                        DB::table('fi_catalog_facts')->updateOrInsert(
                            ['image_id' => $iid, 'fact' => $fact, 'method_version' => self::VERSION],
                            ['product_id' => $pids[$iid], 'status' => $f['status'], 'value' => json_encode($f['value']),
                                'method' => mb_substr($f['method'], 0, 191), 'confidence' => $f['confidence'], 'updated_at' => now(), 'created_at' => now()]);
                    }
                    DB::table('fi_catalog_images')->where('image_id', $iid)->update(['analysis_status' => 'done', 'analysis_version' => self::VERSION,
                        'width' => $r['width'], 'height' => $r['height'], 'dhash' => $r['dhash'], 'error' => null, 'analysed_at' => now()]);
                    $done++;
                }
            });
            $last = $chunk->last()->image_id;
            DB::table('fi_catalog_runs')->where('id', $runId)->update(['processed' => $done, 'errors' => $errs, 'last_image_id' => $last, 'updated_at' => now()]);
            file_put_contents($dir.'/catalog_checkpoint.json', json_encode(['run_id' => $runId, 'version' => self::VERSION, 'done' => $done, 'errors' => $errs, 'total' => $total, 'last_image_id' => $last, 'at' => now()->toDateTimeString()]));
            $this->line(sprintf('[%s] %d/%d done, %d errors', now()->toTimeString(), $done, $total, $errs));
        }
        @unlink($dir.'/catalog_in_'.$runId.'.json');
        @unlink($dir.'/catalog_out_'.$runId.'.json');
        DB::table('fi_catalog_runs')->where('id', $runId)->update(['status' => 'done', 'finished_at' => now(), 'updated_at' => now()]);
    }

    /** Near-duplicate: dHash hamming distance <= 4 (lowest image_id is the anchor). */
    protected function dedupeNear(): void
    {
        $rows = DB::table('fi_catalog_images')->whereNotNull('dhash')->orderBy('image_id')->get(['image_id', 'dhash']);
        $h = $rows->map(fn ($r) => [$r->image_id, hexdec(substr($r->dhash, 0, 8)), hexdec(substr($r->dhash, 8, 8))])->all();
        $n = count($h);
        $anchor = [];
        for ($i = 0; $i < $n; $i++) {
            if (isset($anchor[$h[$i][0]])) {
                continue;
            }
            for ($j = $i + 1; $j < $n; $j++) {
                if (! isset($anchor[$h[$j][0]]) && (substr_count(decbin($h[$i][1] ^ $h[$j][1]), '1') + substr_count(decbin($h[$i][2] ^ $h[$j][2]), '1')) <= 4) {
                    $anchor[$h[$j][0]] = $h[$i][0];
                }
            }
        }
        DB::table('fi_catalog_images')->update(['near_dup_of' => null]);
        foreach ($anchor as $img => $a) {
            DB::table('fi_catalog_images')->where('image_id', $img)->update(['near_dup_of' => $a]);
        }
    }

    protected function rollupItems(): void
    {
        DB::statement('UPDATE fi_catalog_items it LEFT JOIN (
              SELECT a.product_id, COUNT(DISTINCT b.product_id) c FROM fi_catalog_images a
              JOIN fi_catalog_images b ON a.product_id <> b.product_id AND (a.sha256 = b.sha256
                   OR COALESCE(a.near_dup_of,a.image_id) = COALESCE(b.near_dup_of,b.image_id))
              GROUP BY a.product_id) x ON x.product_id = it.product_id
            SET it.dup_image_products = COALESCE(x.c, 0)');
    }
}
