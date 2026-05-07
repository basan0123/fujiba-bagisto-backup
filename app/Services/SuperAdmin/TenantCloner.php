<?php
namespace App\Services\SuperAdmin;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Clones a subset of a source tenant's catalog into a freshly-migrated target
 * tenant DB. Strategy:
 *
 *  - Both DBs are addressable on the same MySQL connection, so we can use
 *    `INSERT INTO target.x SELECT FROM source.x WHERE ...` to copy in bulk
 *    without round-tripping through PHP.
 *  - The target DB is freshly migrated (empty data). We preserve original
 *    integer PKs end-to-end so we never have to remap FKs.
 *  - Every `INSERT ... SELECT` is wrapped with `INSERT IGNORE` so the step is
 *    idempotent if a partial run was retried.
 *  - DDL is never wrapped in DB::transaction (per project memory).
 */
class TenantCloner
{
    /**
     * Tables that need full copies regardless of channel selection (because
     * channel-scoped data references them and IDs must match).
     *
     * Order matters when there are FKs that point at the same table. Roughly:
     * lookup tables → core config → catalog metadata → product subtree.
     */
    private const GLOBAL_COPY_TABLES = [
        // Locale / currency — channel rows reference these.
        'locales',
        'currencies',
        'currency_exchange_rates',
        // Country / state lookups for tax + addresses.
        'countries',
        'country_states',
        'country_translations',
        'country_state_translations',
        // Tax catalog.
        'tax_categories',
        'tax_rates',
        'tax_categories_tax_rates',
        // Customer groups (referenced by product_customer_group_prices and
        // catalog rules; only the group definitions, not actual customers).
        'customer_groups',
        // Inventory sources — channels reference inventory source IDs.
        'inventory_sources',
        // Attribute system — products reference these and they're shared.
        'attributes',
        'attribute_options',
        'attribute_translations',
        'attribute_option_translations',
        'attribute_groups',
        'attribute_families',
        'attribute_group_mappings',
        // CMS pages (channel filtering done via cms_page_channels pivot).
        'cms_pages',
        'cms_page_translations',
        // Theme customization.
        'theme_customizations',
        'theme_customization_translations',
    ];

    /**
     * Populate a freshly-created tenant DB with the master template's full
     * schema + seed data via mysqldump | mysql. This guarantees every Bagisto
     * package's tables exist (which `tenants:migrate` alone does NOT, because
     * tenancy.php's --path list only registers a subset).
     *
     * After this, `tenants:migrate` runs and applies any migrations that
     * post-date the template snapshot.
     */
    public function seedFromMasterTemplate(Tenant $target): void
    {
        $master = 'tenant_master_template';
        $targetDb = $target->database()->getName();

        $cfg = config('database.connections.mysql');
        $user = (string) ($cfg['username'] ?? '');
        $pass = (string) ($cfg['password'] ?? '');
        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (int)    ($cfg['port'] ?? 3306);

        // Sanity check: master template must exist.
        $exists = DB::selectOne(
            "SELECT 1 e FROM information_schema.schemata WHERE schema_name = ?",
            [$master]
        );
        if (! $exists) {
            throw new \RuntimeException("Master template DB '{$master}' is missing on this server.");
        }

        // Pipe mysqldump → mysql. Crucial: stderr from mysqldump must NOT
        // bleed into mysql's stdin (mysqldump's "Using a password..." warning
        // would otherwise be parsed as SQL and crash). Pipe stdout only,
        // route all stderr to a file we can include in the error message.
        // Use `set -o pipefail` so a mysqldump failure surfaces as nonzero
        // even though mysql succeeds reading nothing.
        $stderrFile = tempnam(sys_get_temp_dir(), 'tcseed_');

        $cmd = sprintf(
            'set -o pipefail; mysqldump --host=%s --port=%d --user=%s --password=%s --no-tablespaces --add-drop-table --add-drop-trigger --routines --triggers --skip-lock-tables %s 2>%s | mysql --host=%s --port=%d --user=%s --password=%s %s 2>>%s',
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($master),
            escapeshellarg($stderrFile),
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($targetDb),
            escapeshellarg($stderrFile),
        );

        $output = [];
        $exitCode = 0;
        exec('bash -c ' . escapeshellarg($cmd), $output, $exitCode);

        $stderr = is_readable($stderrFile) ? (string) file_get_contents($stderrFile) : '';
        @unlink($stderrFile);

        // mysqldump always prints a warning to stderr when --password is on
        // the command line; that's noise, not an error. Only fail on nonzero
        // exit code.
        if ($exitCode !== 0) {
            throw new \RuntimeException(
                "mysqldump | mysql failed (exit={$exitCode}): "
                . substr(trim($stderr), 0, 1500)
            );
        }
    }

    public function wipeSeed(Tenant $target): void
    {
        $db = $target->database()->getName();

        // FK checks off so we can wipe in any order. Re-enable at end.
        DB::statement("SET FOREIGN_KEY_CHECKS=0");
        try {
            foreach ([
                'channel_locales',
                'channel_currencies',
                'channel_inventory_sources',
                'channel_translations',
                'channels',
                'category_translations',
                'category_filterable_attributes',
                'categories',
                'attribute_group_mappings',
                'attribute_translations',
                'attribute_option_translations',
                'attribute_options',
                'attribute_groups',
                'attribute_families',
                'attributes',
                'currency_exchange_rates',
                'currencies',
                'locales',
            ] as $t) {
                DB::statement("TRUNCATE TABLE `{$db}`.`{$t}`");
            }
        } finally {
            DB::statement("SET FOREIGN_KEY_CHECKS=1");
        }
    }

    public function copyCatalog(string $sourceTenantId, Tenant $targetTenant, array $channelCodes): string
    {
        $sourceDb = 'tenant_' . $sourceTenantId;
        $targetDb = $targetTenant->database()->getName();

        // 1. Resolve the chosen channels in the source.
        $channels = DB::select(
            "SELECT id, code, root_category_id, default_locale_id, base_currency_id
             FROM `{$sourceDb}`.channels
             WHERE code IN (" . $this->placeholders($channelCodes) . ")",
            $channelCodes
        );

        if (empty($channels)) {
            throw new \RuntimeException('No matching channels found in source tenant.');
        }

        $channelIds = array_map(fn ($c) => $c->id, $channels);
        $rootCategoryIds = array_unique(array_filter(array_map(fn ($c) => $c->root_category_id, $channels)));

        DB::statement("SET FOREIGN_KEY_CHECKS=0");
        try {
            // 2. Lookup tables that channels and the catalog depend on.
            $this->copyLookupTables($sourceDb, $targetDb);

            // 3. Channels themselves + their pivots (only the chosen ones).
            $this->copyChannels($sourceDb, $targetDb, $channelIds);

            // 4. Categories: only subtrees rooted at the chosen channels' root
            //    category, plus the root rows themselves.
            $categoryIds = $this->copyCategoryTrees($sourceDb, $targetDb, $rootCategoryIds);

            // 5. Products on those channels (and only those).
            $productIds = $this->copyProducts($sourceDb, $targetDb, $channelIds, $categoryIds);

            // 6. CMS pages on those channels.
            $this->copyCmsPages($sourceDb, $targetDb, $channelIds);

            // 7. Theme customizations linked to those channels.
            $this->copyThemes($sourceDb, $targetDb, $channelIds);

            return sprintf(
                'Catalog: channels=%d categories=%d products=%d',
                count($channelIds),
                count($categoryIds),
                count($productIds),
            );
        } finally {
            DB::statement("SET FOREIGN_KEY_CHECKS=1");
        }
    }

    public function rsyncImages(string $sourceTenantId, string $targetTenantId): string
    {
        // stancl/tenancy stores per-tenant public files under sharded paths:
        //   storage/app/public/tenants/<shard>/<tenant-uuid>/...
        // We rsync every shard that has a matching source dir.
        $base = '/var/www/bagisto/storage/app/public/tenants';

        if (! is_dir($base)) {
            return "Tenants storage base not found ({$base}); skipped.";
        }

        $shards = array_filter(scandir($base) ?: [], fn ($x) => $x !== '.' && $x !== '..' && is_dir("{$base}/{$x}"));
        $copied = [];

        foreach ($shards as $shard) {
            $src = "{$base}/{$shard}/{$sourceTenantId}";
            $dst = "{$base}/{$shard}/{$targetTenantId}";
            if (! is_dir($src)) {
                continue;
            }
            if (! is_dir($dst)) {
                @mkdir($dst, 0755, true);
            }
            $cmd = sprintf('rsync -a %s/ %s/ 2>&1', escapeshellarg($src), escapeshellarg($dst));
            shell_exec($cmd);
            $size = (int) (shell_exec('du -sb ' . escapeshellarg($dst) . ' 2>/dev/null | cut -f1') ?: 0);
            $copied[] = "shard {$shard}: " . round($size / (1024 * 1024), 1) . 'MB';
        }

        if (! $copied) {
            return "No tenant image dirs found for source {$sourceTenantId}; skipped.";
        }
        return 'Rsynced — ' . implode(', ', $copied);
    }

    public function createAdmin(Tenant $tenant, string $email, string $password): void
    {
        $db = $tenant->database()->getName();

        // Resolve role id in the target. If migrations seeded an Administrator
        // role we use it; otherwise insert a minimal admin role.
        $roleId = DB::selectOne("SELECT id FROM `{$db}`.roles WHERE LOWER(name) IN ('administrator','admin') ORDER BY id ASC LIMIT 1");
        if (! $roleId) {
            DB::statement("
                INSERT INTO `{$db}`.roles (name, description, permission_type, permissions, created_at, updated_at)
                VALUES ('Administrator', 'Auto-created on tenant clone', 'all', NULL, NOW(), NOW())
            ");
            $roleId = DB::selectOne("SELECT LAST_INSERT_ID() AS id");
        }

        DB::statement(
            "INSERT INTO `{$db}`.admins (name, email, password, status, role_id, created_at, updated_at)
             VALUES (?, ?, ?, 1, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE password = VALUES(password), status = 1, role_id = VALUES(role_id), updated_at = NOW()",
            [
                'Administrator',
                $email,
                Hash::make($password),
                $roleId->id,
            ]
        );
    }

    // ─── INTERNALS ──────────────────────────────────────────────────────

    private function copyLookupTables(string $src, string $dst): void
    {
        foreach (self::GLOBAL_COPY_TABLES as $table) {
            // Skip tables that don't exist in source (older tenants).
            $exists = DB::selectOne(
                "SELECT 1 AS e FROM information_schema.tables WHERE table_schema=? AND table_name=?",
                [$src, $table]
            );
            if (! $exists) {
                continue;
            }
            // INSERT IGNORE so re-runs don't duplicate.
            DB::statement("INSERT IGNORE INTO `{$dst}`.`{$table}` SELECT * FROM `{$src}`.`{$table}`");
        }
    }

    private function copyChannels(string $src, string $dst, array $channelIds): void
    {
        $in = $this->intIn($channelIds);

        DB::statement("INSERT IGNORE INTO `{$dst}`.channels SELECT * FROM `{$src}`.channels WHERE id IN ({$in})");
        DB::statement("INSERT IGNORE INTO `{$dst}`.channel_translations SELECT * FROM `{$src}`.channel_translations WHERE channel_id IN ({$in})");
        DB::statement("INSERT IGNORE INTO `{$dst}`.channel_locales SELECT * FROM `{$src}`.channel_locales WHERE channel_id IN ({$in})");
        DB::statement("INSERT IGNORE INTO `{$dst}`.channel_currencies SELECT * FROM `{$src}`.channel_currencies WHERE channel_id IN ({$in})");
        DB::statement("INSERT IGNORE INTO `{$dst}`.channel_inventory_sources SELECT * FROM `{$src}`.channel_inventory_sources WHERE channel_id IN ({$in})");
    }

    private function copyCategoryTrees(string $src, string $dst, array $rootIds): array
    {
        if (empty($rootIds)) {
            return [];
        }

        $rootIn = $this->intIn($rootIds);

        // Subtree using nested-set semantics: lft >= root.lft AND rgt <= root.rgt
        // for every root in $rootIds. Plus the roots themselves.
        $rows = DB::select("
            SELECT c.id
            FROM `{$src}`.categories c
            JOIN `{$src}`.categories r ON r.id IN ({$rootIn})
            WHERE c._lft >= r._lft AND c._rgt <= r._rgt
        ");

        $ids = array_unique(array_map(fn ($r) => $r->id, $rows));
        if (empty($ids)) {
            return [];
        }

        $in = $this->intIn($ids);

        DB::statement("INSERT IGNORE INTO `{$dst}`.categories SELECT * FROM `{$src}`.categories WHERE id IN ({$in})");
        DB::statement("INSERT IGNORE INTO `{$dst}`.category_translations SELECT * FROM `{$src}`.category_translations WHERE category_id IN ({$in})");

        // Optional table — present in some tenants only.
        if (DB::selectOne("SELECT 1 e FROM information_schema.tables WHERE table_schema=? AND table_name=?", [$src, 'category_filterable_attributes'])) {
            DB::statement("INSERT IGNORE INTO `{$dst}`.category_filterable_attributes SELECT * FROM `{$src}`.category_filterable_attributes WHERE category_id IN ({$in})");
        }

        return $ids;
    }

    private function copyProducts(string $src, string $dst, array $channelIds, array $categoryIds): array
    {
        $chIn = $this->intIn($channelIds);

        // Products on the chosen channels — pull all parents to be safe (a
        // configurable child without its parent would render but never sell).
        $productRows = DB::select("
            SELECT DISTINCT p.id
            FROM `{$src}`.products p
            JOIN `{$src}`.product_channels pc ON pc.product_id = p.id
            WHERE pc.channel_id IN ({$chIn})
        ");
        $productIds = array_map(fn ($r) => $r->id, $productRows);

        // Pull parent_ids of these products so configurable parents land too.
        $parents = DB::select("
            SELECT DISTINCT parent_id
            FROM `{$src}`.products
            WHERE id IN (" . ($productIds ? $this->intIn($productIds) : '0') . ")
              AND parent_id IS NOT NULL
        ");
        $productIds = array_unique(array_merge($productIds, array_map(fn ($r) => $r->parent_id, $parents)));

        if (empty($productIds)) {
            return [];
        }

        $pIn = $this->intIn($productIds);

        // products + product_flat first (FK target for many tables).
        DB::statement("INSERT IGNORE INTO `{$dst}`.products SELECT * FROM `{$src}`.products WHERE id IN ({$pIn})");

        // product_flat may have rows for channel/locale combos we don't have;
        // copy only flat rows whose channel IS in the picked set OR whose
        // channel column is empty (some Bagisto installs use NULL channel).
        $codesQuoted = "''";
        if ($channelCodes = $this->channelCodesFor($src, $channelIds)) {
            $codesQuoted = implode(',', array_map(fn ($c) => "'" . addslashes($c) . "'", $channelCodes));
        }

        DB::statement("
            INSERT IGNORE INTO `{$dst}`.product_flat
            SELECT * FROM `{$src}`.product_flat
            WHERE product_id IN ({$pIn})
              AND (channel IS NULL OR channel IN ({$codesQuoted}))
        ");

        // Per-product children tables.
        $childTables = [
            'product_attribute_values',
            'product_super_attributes',
            'product_categories',
            'product_channels',
            'product_images',
            'product_videos',
            'product_inventories',
            'product_inventory_indices',
            'product_price_indices',
            'product_customer_group_prices',
            'product_relations',
            'product_up_sells',
            'product_cross_sells',
            'product_grouped_products',
            'product_bundle_options',
            'product_bundle_option_products',
            'product_bundle_option_translations',
            'product_customizable_options',
            'product_customizable_option_prices',
            'product_customizable_option_translations',
            'product_downloadable_links',
            'product_downloadable_link_translations',
            'product_downloadable_samples',
            'product_downloadable_sample_translations',
            'product_shipping_exclusions',
        ];

        foreach ($childTables as $table) {
            if (! $this->tableExists($src, $table)) {
                continue;
            }
            // Most of these have a `product_id` column.
            $hasCol = DB::selectOne(
                "SELECT 1 e FROM information_schema.columns WHERE table_schema=? AND table_name=? AND column_name='product_id'",
                [$src, $table]
            );
            if (! $hasCol) {
                continue;
            }
            DB::statement("INSERT IGNORE INTO `{$dst}`.`{$table}` SELECT * FROM `{$src}`.`{$table}` WHERE product_id IN ({$pIn})");
        }

        // Restrict product_categories to copied category set — drops any rows
        // that point to a category outside the cloned trees.
        if (! empty($categoryIds)) {
            $catIn = $this->intIn($categoryIds);
            DB::statement("DELETE FROM `{$dst}`.product_categories WHERE category_id NOT IN ({$catIn})");
        }

        // Restrict product_channels to the chosen channels.
        DB::statement("DELETE FROM `{$dst}`.product_channels WHERE channel_id NOT IN ({$chIn})");

        // Stock zeroed per spec — orders/customers aren't copied so existing
        // stock would mislead the new owner.
        if ($this->tableExists($dst, 'product_inventories')) {
            DB::statement("UPDATE `{$dst}`.product_inventories SET qty = 0");
        }
        if ($this->tableExists($dst, 'product_inventory_indices')) {
            DB::statement("UPDATE `{$dst}`.product_inventory_indices SET qty = 0");
        }

        return $productIds;
    }

    private function copyCmsPages(string $src, string $dst, array $channelIds): void
    {
        if (! $this->tableExists($src, 'cms_page_channels')) {
            return;
        }
        $chIn = $this->intIn($channelIds);
        $pageRows = DB::select("SELECT DISTINCT cms_page_id FROM `{$src}`.cms_page_channels WHERE channel_id IN ({$chIn})");
        $pageIds = array_map(fn ($r) => $r->cms_page_id, $pageRows);
        if (empty($pageIds)) {
            return;
        }
        $in = $this->intIn($pageIds);
        DB::statement("INSERT IGNORE INTO `{$dst}`.cms_pages SELECT * FROM `{$src}`.cms_pages WHERE id IN ({$in})");
        DB::statement("INSERT IGNORE INTO `{$dst}`.cms_page_translations SELECT * FROM `{$src}`.cms_page_translations WHERE cms_page_id IN ({$in})");
        DB::statement("INSERT IGNORE INTO `{$dst}`.cms_page_channels SELECT * FROM `{$src}`.cms_page_channels WHERE cms_page_id IN ({$in}) AND channel_id IN ({$chIn})");
    }

    private function copyThemes(string $src, string $dst, array $channelIds): void
    {
        if (! $this->tableExists($src, 'theme_customizations')) {
            return;
        }
        $chIn = $this->intIn($channelIds);

        // theme_customizations has channel_id; pull rows for picked channels.
        DB::statement("INSERT IGNORE INTO `{$dst}`.theme_customizations SELECT * FROM `{$src}`.theme_customizations WHERE channel_id IN ({$chIn})");
        if ($this->tableExists($src, 'theme_customization_translations')) {
            DB::statement("
                INSERT IGNORE INTO `{$dst}`.theme_customization_translations
                SELECT t.* FROM `{$src}`.theme_customization_translations t
                JOIN `{$src}`.theme_customizations tc ON tc.id = t.theme_customization_id
                WHERE tc.channel_id IN ({$chIn})
            ");
        }
    }

    private function channelCodesFor(string $src, array $channelIds): array
    {
        if (empty($channelIds)) {
            return [];
        }
        $in = $this->intIn($channelIds);
        $rows = DB::select("SELECT code FROM `{$src}`.channels WHERE id IN ({$in})");
        return array_map(fn ($r) => $r->code, $rows);
    }

    private function tableExists(string $db, string $table): bool
    {
        return (bool) DB::selectOne(
            "SELECT 1 e FROM information_schema.tables WHERE table_schema=? AND table_name=?",
            [$db, $table]
        );
    }

    private function intIn(array $ids): string
    {
        $clean = array_filter(array_map('intval', $ids), fn ($v) => $v !== null);
        return $clean ? implode(',', array_unique($clean)) : '0';
    }

    private function placeholders(array $values): string
    {
        return implode(',', array_fill(0, count($values), '?'));
    }
}
