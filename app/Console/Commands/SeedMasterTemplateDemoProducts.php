<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Product\Helpers\Indexers\Flat;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Repositories\ProductRepository;

/**
 * One-off: seed a small demo catalog into the `tenant_master_template`
 * database so EVERY newly-cloned tenant is born with working, sellable
 * products (visible in the storefront AND the POS catalog).
 *
 * Why this command and not raw INSERTs: products must be created through the
 * ProductRepository / type instance so they get flattened into product_flat
 * and their inventory indexed. A raw insert leaves the product invisible
 * (the exact "product exists but not in product_flat" bug seen on kkmart).
 *
 * Idempotent: skips any SKU it already finds, so re-running is safe.
 *
 * Run ONCE against the template:
 *   php artisan fujiba:seed-master-demo-products
 *
 * The template ships channel=default (locale en / currency USD); the per-tenant
 * provisioning re-flattens for ja after the clone, so seeding in `en` here is
 * correct. Prices are plain numbers (currency is display-only).
 */
class SeedMasterTemplateDemoProducts extends Command
{
    protected $signature = 'fujiba:seed-master-demo-products {--connection=tenant_master_template : Target DB connection name}';

    protected $description = 'Seed demo products into tenant_master_template so new tenants start with a working catalog';

    /** The starter catalog every new shop inherits. */
    private array $demo = [
        ['sku' => 'DEMO-WATER-500',   'name' => 'Mineral Water 500ml', 'price' => 120, 'qty' => 50, 'weight' => 0.5],
        ['sku' => 'DEMO-GREENTEA',    'name' => 'Green Tea (bottle)',  'price' => 150, 'qty' => 40, 'weight' => 0.5],
        ['sku' => 'DEMO-ONIGIRI',     'name' => 'Rice Ball (Onigiri)', 'price' => 180, 'qty' => 30, 'weight' => 0.12],
        ['sku' => 'DEMO-BENTO',       'name' => 'Bento Box',           'price' => 650, 'qty' => 20, 'weight' => 0.6],
        ['sku' => 'DEMO-COFFEE',      'name' => 'Hot Coffee',          'price' => 300, 'qty' => 25, 'weight' => 0.25],
        ['sku' => 'DEMO-CHOCO',       'name' => 'Chocolate Bar',       'price' => 200, 'qty' => 60, 'weight' => 0.08],
    ];

    public function handle(): int
    {
        $conn = (string) $this->option('connection');

        if (! array_key_exists($conn, config('database.connections', []))) {
            // The provisioning code addresses the template DB directly by name
            // via the central/mysql connection's host. Build an ad-hoc copy of
            // the mysql connection pointed at the template DB so Eloquent can use it.
            $base = config('database.connections.mysql');
            $base['database'] = $conn; // connection name == database name here
            Config::set('database.connections.'.$conn, $base);
        }

        // Make the repository/indexers operate on the template DB by pointing the
        // DEFAULT connection at it for the duration of this command.
        $previousDefault = config('database.default');
        Config::set('database.default', $conn);
        DB::purge($conn);
        DB::setDefaultConnection($conn);

        $this->info("Seeding demo products into connection [{$conn}] ...");

        $familyId = DB::table('attribute_families')->where('code', 'default')->value('id')
            ?? DB::table('attribute_families')->min('id');
        $channel  = DB::table('channels')->where('code', 'default')->first()
            ?? DB::table('channels')->first();
        $locale   = DB::table('locales')->where('id', $channel->default_locale_id)->value('code') ?? 'en';
        $inventorySourceId = DB::table('inventory_sources')->where('code', 'default')->value('id')
            ?? DB::table('inventory_sources')->min('id');

        if (! $familyId || ! $channel || ! $inventorySourceId) {
            $this->error('Template is missing attribute family / channel / inventory source — aborting.');
            Config::set('database.default', $previousDefault);
            DB::setDefaultConnection($previousDefault);

            return self::FAILURE;
        }

        $rootCategoryId = DB::table('categories')->min('id');

        /** @var ProductRepository $repo */
        $repo = app(ProductRepository::class);

        $created = 0;
        $skipped = 0;

        foreach ($this->demo as $row) {
            if (DB::table('products')->where('sku', $row['sku'])->exists()) {
                $this->line("  skip (exists): {$row['sku']}");
                $skipped++;

                continue;
            }

            // 1) Skeleton — same as the admin "create" step.
            $product = $repo->create([
                'type'                => 'simple',
                'attribute_family_id' => $familyId,
                'sku'                 => $row['sku'],
            ]);

            // 2) Fill attributes + inventory + channel/category links — the admin
            //    "update" step. This is what triggers flat + inventory indexing.
            $repo->update([
                'channel'              => $channel->code,
                'locale'               => $locale,
                'sku'                  => $row['sku'],
                'name'                 => $row['name'],
                'url_key'              => Str::slug($row['name']).'-'.Str::lower(Str::random(4)),
                'short_description'    => $row['name'],
                'description'          => $row['name'],
                'price'                => $row['price'],
                'weight'              => $row['weight'],
                'status'               => 1,
                'visible_individually' => 1,
                'guest_checkout'       => 1,
                'product_number'       => $row['sku'],
                'meta_title'           => $row['name'],
                'meta_keywords'        => $row['name'],
                'meta_description'     => $row['name'],
                'categories'           => $rootCategoryId ? [$rootCategoryId] : [],
                'inventories'          => [$inventorySourceId => $row['qty']],
                'channels'             => [$channel->id],
            ], $product->id);

            $this->line("  created: {$row['sku']}  {$row['name']}  ¥{$row['price']}  qty {$row['qty']}");
            $created++;
        }

        // The flat + price indexers normally run via QUEUED jobs (QUEUE=redis),
        // so they did NOT execute synchronously above — leaving product_flat and
        // the price index empty (the exact "invisible product" symptom). Run both
        // indexers INLINE now, while the default connection still points at the
        // template DB, so the clone inherits a fully-indexed, sellable catalog.
        $this->info('Reindexing flat + price for the template ...');
        app(Flat::class)->reindexFull();
        app(PriceIndexer::class)->reindexFull();

        // Restore the default connection so nothing else in the process is affected.
        Config::set('database.default', $previousDefault);
        DB::setDefaultConnection($previousDefault);

        $this->info("Done. created={$created} skipped={$skipped}");

        return self::SUCCESS;
    }
}
