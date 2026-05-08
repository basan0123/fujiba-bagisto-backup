<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncChatpion extends Command
{
    protected $signature = 'chatpion:sync
        {--tenant-db= : Bagisto tenant database name}
        {--stage-db=chatpion_stage : Staging database loaded by bash wrapper}
        {--entity=all : products|customers|orders|all}
        {--store=all : 9|10|all}
        {--apply : Write changes (otherwise dry-run)}';

    protected $description = 'Incremental ChatPion -> Bagisto sync (upsert products, customers by subscribe_id, insert-only orders).';

    protected string $bg;
    protected string $stage;
    protected bool $apply = false;

    protected array $storeMap = [
        9  => ['channel' => 2, 'sku' => 'MMFOOD', 'order' => 'MF'],
        10 => ['channel' => 1, 'sku' => 'KIMONO', 'order' => 'KM'],
    ];

    public function handle(): int
    {
        $this->bg    = (string) ($this->option('tenant-db') ?: config('database.connections.mysql.database'));
        $this->stage = (string) $this->option('stage-db');
        $this->apply = (bool) $this->option('apply');

        if ($this->bg === '' || $this->stage === '') {
            $this->error('tenant-db and stage-db are required.');
            return self::FAILURE;
        }

        $stores = $this->option('store') === 'all'
            ? array_keys($this->storeMap)
            : [(int) $this->option('store')];

        foreach ($stores as $sid) {
            if (! isset($this->storeMap[$sid])) {
                $this->error("Unknown store_id={$sid}");
                return self::FAILURE;
            }
        }

        $this->ensureSchema();

        $entity = (string) $this->option('entity');

        try {
            foreach ($stores as $sid) {
                $map = $this->storeMap[$sid];
                $this->line("=== store={$sid} channel={$map['channel']} ({$map['sku']}-*) ===");
                if (in_array($entity, ['customers', 'all'], true)) $this->syncCustomers($sid, $map);
                if (in_array($entity, ['products',  'all'], true)) $this->syncProducts($sid, $map);
                if (in_array($entity, ['orders',    'all'], true)) $this->syncOrders($sid, $map);
            }
        } catch (Throwable $e) {
            Log::error('chatpion:sync failed', ['e' => $e->getMessage()]);
            $this->error('FAILED: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->info($this->apply ? 'Applied.' : 'Dry-run complete (no writes).');
        return self::SUCCESS;
    }

    protected function ensureSchema(): void
    {
        $col = DB::selectOne("SELECT COUNT(*) c FROM information_schema.columns
            WHERE table_schema = ? AND table_name='customers' AND column_name='chatpion_subscribe_id'",
            [$this->bg]);

        if ((int) $col->c === 0) {
            if (! $this->apply) {
                $this->warn("customers.chatpion_subscribe_id missing. Run with --apply (or bash wrapper --init-schema) to add it.");
                throw new \RuntimeException('schema not initialized');
            }
            DB::statement("ALTER TABLE `{$this->bg}`.customers
                ADD COLUMN chatpion_subscribe_id VARCHAR(64) NULL,
                ADD UNIQUE KEY customers_chatpion_subscribe_id_channel_unique (chatpion_subscribe_id, channel_id)");
            DB::statement("UPDATE `{$this->bg}`.customers
                SET chatpion_subscribe_id = SUBSTRING_INDEX(email,'@',1)
                WHERE chatpion_subscribe_id IS NULL
                  AND email LIKE '%@messenger.local'");
            $this->info('Added customers.chatpion_subscribe_id + backfilled.');
        }
    }

    protected function syncProducts(int $storeId, array $map): void
    {
        $ch = $map['channel']; $pref = $map['sku'];

        $count   = (int) DB::selectOne("SELECT COUNT(*) c FROM `{$this->stage}`.ecommerce_product WHERE store_id = ? AND deleted='0'", [$storeId])->c;
        $matched = (int) DB::selectOne("
            SELECT COUNT(*) c
              FROM `{$this->stage}`.ecommerce_product ep
              JOIN `{$this->bg}`.products p ON p.sku = CONCAT(?, '-', ep.id)
             WHERE ep.store_id = ? AND ep.deleted='0'", [$pref, $storeId])->c;

        $this->line("  products: staged={$count} matched_in_bagisto={$matched}");

        if (! $this->apply) return;

        $channelCode = DB::selectOne("SELECT code FROM `{$this->bg}`.channels WHERE id = ?", [$ch])->code;

        DB::transaction(function () use ($storeId, $pref, $channelCode) {
            // product_flat price/cost/special
            DB::statement("
                UPDATE `{$this->bg}`.product_flat pf
                  JOIN `{$this->bg}`.products p ON p.id = pf.product_id
                  JOIN `{$this->stage}`.ecommerce_product ep ON p.sku = CONCAT(?, '-', ep.id)
                   SET pf.price = ep.original_price,
                       pf.special_price = NULL,
                       pf.updated_at = NOW()
                 WHERE ep.store_id = ? AND ep.deleted='0' AND pf.channel = ?",
                [$pref, $storeId, $channelCode]);

            // EAV: price(11), cost(12)
            foreach ([11 => 'ep.original_price',
                      12 => 'ep.cost_jpy'] as $aid => $expr) {
                DB::statement("
                    INSERT INTO `{$this->bg}`.product_attribute_values
                        (product_id, attribute_id, channel, locale, float_value, unique_id)
                    SELECT p.id, ?, NULL, NULL, {$expr}, CONCAT(p.id,'|',?,'||')
                      FROM `{$this->stage}`.ecommerce_product ep
                      JOIN `{$this->bg}`.products p ON p.sku = CONCAT(?, '-', ep.id)
                     WHERE ep.store_id = ? AND ep.deleted='0'
                    ON DUPLICATE KEY UPDATE float_value = VALUES(float_value)",
                    [$aid, $aid, $pref, $storeId]);
            }

            // EAV: original_product_name (attr 37, text). Skip null/empty.
            DB::statement("
                INSERT INTO `{$this->bg}`.product_attribute_values
                    (product_id, attribute_id, channel, locale, text_value, unique_id)
                SELECT p.id, 37, NULL, NULL, ep.original_product_name, CONCAT(p.id,'|37||')
                  FROM `{$this->stage}`.ecommerce_product ep
                  JOIN `{$this->bg}`.products p ON p.sku = CONCAT(?, '-', ep.id)
                 WHERE ep.store_id = ? AND ep.deleted='0'
                   AND ep.original_product_name IS NOT NULL
                   AND ep.original_product_name <> ''
                ON DUPLICATE KEY UPDATE text_value = VALUES(text_value)",
                [$pref, $storeId]);

            // stock -> inventory_source_id=1 + mirror to product_flat
            DB::statement("
                INSERT INTO `{$this->bg}`.product_inventories (product_id, inventory_source_id, qty, vendor_id)
                SELECT p.id, 1, COALESCE(ep.stock_item,0), 0
                  FROM `{$this->stage}`.ecommerce_product ep
                  JOIN `{$this->bg}`.products p ON p.sku = CONCAT(?, '-', ep.id)
                 WHERE ep.store_id = ? AND ep.deleted='0' AND ep.stock_item IS NOT NULL
                ON DUPLICATE KEY UPDATE qty = VALUES(qty)",
                [$pref, $storeId]);

        });

        $this->info('  products: applied.');
    }

    protected function syncCustomers(int $storeId, array $map): void
    {
        $ch = $map['channel'];

        $staged = (int) DB::selectOne("SELECT COUNT(*) c FROM `{$this->stage}`.messenger_bot_subscriber")->c;
        $toIns  = (int) DB::selectOne("
            SELECT COUNT(*) c
              FROM `{$this->stage}`.messenger_bot_subscriber s
              LEFT JOIN `{$this->bg}`.customers c
                     ON c.chatpion_subscribe_id = s.subscribe_id AND c.channel_id = ?
             WHERE c.id IS NULL", [$ch])->c;
        $toUpd  = (int) DB::selectOne("
            SELECT COUNT(*) c
              FROM `{$this->stage}`.messenger_bot_subscriber s
              JOIN `{$this->bg}`.customers c
                ON c.chatpion_subscribe_id = s.subscribe_id AND c.channel_id = ?", [$ch])->c;

        $this->line("  customers: staged={$staged} insert={$toIns} update={$toUpd}");

        if (! $this->apply) return;

        $groupId = DB::selectOne("SELECT id FROM `{$this->bg}`.customer_groups WHERE code='general' LIMIT 1")->id ?? null;

        DB::transaction(function () use ($ch, $groupId) {
            DB::statement("
                INSERT INTO `{$this->bg}`.customers
                    (first_name, last_name, email, phone, chatpion_subscribe_id,
                     status, channel_id, customer_group_id, created_at, updated_at)
                SELECT NULLIF(s.first_name,''),
                       NULLIF(s.last_name,''),
                       CASE
                         WHEN NULLIF(NULLIF(s.email,''),'0') IS NULL THEN NULL
                         WHEN EXISTS (SELECT 1 FROM `{$this->bg}`.customers cx WHERE cx.email = s.email) THEN NULL
                         WHEN s.id <> (SELECT MIN(s2.id) FROM `{$this->stage}`.messenger_bot_subscriber s2 WHERE s2.email = s.email AND s2.email <> '' AND s2.email <> '0') THEN NULL
                         ELSE s.email
                       END,
                       CASE
                         WHEN NULLIF(s.phone_number,'') IS NULL THEN NULL
                         WHEN EXISTS (SELECT 1 FROM `{$this->bg}`.customers cx WHERE cx.phone = s.phone_number) THEN NULL
                         WHEN s.id <> (SELECT MIN(s2.id) FROM `{$this->stage}`.messenger_bot_subscriber s2 WHERE s2.phone_number = s.phone_number AND s2.phone_number <> '') THEN NULL
                         ELSE s.phone_number
                       END,
                       s.subscribe_id,
                       1, ?, ?, COALESCE(s.subscribed_at, NOW()), NOW()
                  FROM `{$this->stage}`.messenger_bot_subscriber s
                  LEFT JOIN `{$this->bg}`.customers c
                         ON c.chatpion_subscribe_id = s.subscribe_id AND c.channel_id = ?
                 WHERE c.id IS NULL
                   AND s.subscribe_id IS NOT NULL AND s.subscribe_id <> ''",
                [$ch, $groupId, $ch]);

            DB::statement("
                UPDATE `{$this->bg}`.customers c
                  JOIN `{$this->stage}`.messenger_bot_subscriber s
                       ON c.chatpion_subscribe_id = s.subscribe_id AND c.channel_id = ?
                   SET c.first_name = IF(c.first_name IS NULL OR c.first_name='',
                                         NULLIF(s.first_name,''), c.first_name),
                       c.last_name  = IF(c.last_name  IS NULL OR c.last_name ='',
                                         NULLIF(s.last_name ,''), c.last_name),
                       c.phone      = CASE
                                         WHEN c.phone IS NOT NULL AND c.phone <> '' THEN c.phone
                                         WHEN NULLIF(s.phone_number,'') IS NULL THEN c.phone
                                         WHEN EXISTS (SELECT 1 FROM (SELECT id, phone FROM `{$this->bg}`.customers) cx WHERE cx.phone = s.phone_number AND cx.id <> c.id) THEN c.phone
                                         ELSE s.phone_number
                                       END,
                       c.email      = CASE
                                         WHEN c.email IS NOT NULL AND c.email NOT LIKE '%@messenger.local' THEN c.email
                                         WHEN NULLIF(NULLIF(s.email,''),'0') IS NULL THEN c.email
                                         WHEN EXISTS (SELECT 1 FROM (SELECT id, email FROM `{$this->bg}`.customers) cx WHERE cx.email = s.email AND cx.id <> c.id) THEN c.email
                                         ELSE s.email
                                       END,
                       c.updated_at = NOW()", [$ch]);
        });

        $this->info('  customers: applied.');
    }

    protected function syncOrders(int $storeId, array $map): void
    {
        $ch = $map['channel']; $pref = $map['order']; $spref = $map['sku'];

        $staged = (int) DB::selectOne("SELECT COUNT(*) c FROM `{$this->stage}`.ecommerce_cart WHERE store_id = ?", [$storeId])->c;
        $toIns  = (int) DB::selectOne("
            SELECT COUNT(*) c
              FROM `{$this->stage}`.ecommerce_cart ec
              LEFT JOIN `{$this->bg}`.orders o ON o.increment_id = CONCAT(?, '-', ec.id)
             WHERE ec.store_id = ? AND o.id IS NULL", [$pref, $storeId])->c;

        $this->line("  orders: staged={$staged} insert={$toIns}");

        if (! $this->apply) return;

        $channelCode = DB::selectOne("SELECT code FROM `{$this->bg}`.channels WHERE id = ?", [$ch])->code;

        DB::transaction(function () use ($storeId, $ch, $pref, $spref, $channelCode) {
            // ChatPion stores no explicit COD fee column; the fee is the residual
            // payment_amount - subtotal - shipping + discount - tax for COD orders
            // (applies even on store_pickup, since the courier still collects cash).
            // Clamp to >= 0 so any rounding noise on non-COD rows can't go negative.
            $codFeeExpr = "GREATEST(0,
                CASE WHEN ec.payment_method LIKE '%Cash on Delivery%'
                     THEN (ec.payment_amount - ec.subtotal - IF(ec.store_pickup='1',0,ec.shipping) + ec.discount - ec.tax)
                     ELSE 0
                END)";
            $shipExpr = "IF(ec.store_pickup = '1', 0, ec.shipping)";

            DB::statement("
                INSERT INTO `{$this->bg}`.orders
                    (increment_id, status, channel_name, channel_id, channel_type,
                     is_guest, customer_email, customer_first_name, customer_last_name,
                     customer_id, customer_type,
                     base_currency_code, channel_currency_code, order_currency_code,
                     grand_total, base_grand_total, sub_total, base_sub_total,
                     grand_total_invoiced, base_grand_total_invoiced,
                     sub_total_invoiced, base_sub_total_invoiced,
                     shipping_amount, base_shipping_amount,
                     shipping_amount_incl_tax, base_shipping_amount_incl_tax,
                     shipping_method, shipping_title,
                     discount_amount, base_discount_amount,
                     tax_amount, base_tax_amount,
                     cod_fee, base_cod_fee,
                     total_qty_ordered, total_item_count,
                     paid_at,
                     created_at, updated_at)
                SELECT CONCAT(?, '-', ec.id),
                       CASE ec.status
                           WHEN 'pending'   THEN 'pending'
                           WHEN 'approved'  THEN 'processing'
                           WHEN 'shipped'   THEN 'completed'
                           WHEN 'delivered' THEN 'completed'
                           WHEN 'completed' THEN 'completed'
                           WHEN 'rejected'  THEN 'canceled'
                           WHEN 'refunded'  THEN 'closed'
                           ELSE 'pending'
                       END,
                       ?, ?, 'Webkul\\\\Core\\\\Models\\\\Channel',
                       IF(c.id IS NULL, 1, 0),
                       COALESCE(NULLIF(ec.buyer_email,''),      NULLIF(c.email,'')),
                       COALESCE(NULLIF(ec.buyer_first_name,''), NULLIF(c.first_name,'')),
                       COALESCE(NULLIF(ec.buyer_last_name,''),  NULLIF(c.last_name,'')),
                       c.id,
                       IF(c.id IS NULL, NULL, 'Webkul\\\\Customer\\\\Models\\\\Customer'),
                       COALESCE(NULLIF(ec.currency,''),'JPY'),
                       COALESCE(NULLIF(ec.currency,''),'JPY'),
                       COALESCE(NULLIF(ec.currency,''),'JPY'),
                       ec.payment_amount, ec.payment_amount,
                       ec.subtotal, ec.subtotal,
                       IF(ec.status IN ('shipped','delivered','completed'), ec.payment_amount, 0),
                       IF(ec.status IN ('shipped','delivered','completed'), ec.payment_amount, 0),
                       IF(ec.status IN ('shipped','delivered','completed'), ec.subtotal, 0),
                       IF(ec.status IN ('shipped','delivered','completed'), ec.subtotal, 0),
                       {$shipExpr}, {$shipExpr},
                       {$shipExpr}, {$shipExpr},
                       IF(ec.store_pickup = '1', 'free_free', NULL),
                       IF(ec.store_pickup = '1', 'Store Pickup', NULL),
                       ec.discount, ec.discount,
                       ec.tax, ec.tax,
                       {$codFeeExpr}, {$codFeeExpr},
                       (SELECT COALESCE(SUM(quantity),0) FROM `{$this->stage}`.ecommerce_cart_item WHERE cart_id = ec.id),
                       (SELECT COUNT(*) FROM `{$this->stage}`.ecommerce_cart_item WHERE cart_id = ec.id),
                       IF(ec.paid_at IS NOT NULL AND ec.paid_at <> '0000-00-00 00:00:00' AND ec.status IN ('shipped','delivered','completed'), ec.paid_at, NULL),
                       COALESCE(ec.ordered_at, NOW()), COALESCE(ec.updated_at, NOW())
                  FROM `{$this->stage}`.ecommerce_cart ec
                  LEFT JOIN `{$this->bg}`.customers c ON c.chatpion_subscribe_id = ec.subscriber_id AND c.channel_id = ?
                  LEFT JOIN `{$this->bg}`.orders o ON o.increment_id = CONCAT(?, '-', ec.id)
                 WHERE ec.store_id = ? AND o.id IS NULL",
                [$pref, $channelCode, $ch, $ch, $pref, $storeId]);

            DB::statement("
                UPDATE `{$this->bg}`.orders o
                JOIN `{$this->stage}`.ecommerce_cart ec ON o.increment_id = CONCAT(?, '-', ec.id)
                SET o.status = CASE ec.status
                        WHEN 'pending'   THEN 'pending'
                        WHEN 'approved'  THEN 'processing'
                        WHEN 'shipped'   THEN 'completed'
                        WHEN 'delivered' THEN 'completed'
                        WHEN 'completed' THEN 'completed'
                        WHEN 'rejected'  THEN 'canceled'
                        WHEN 'refunded'  THEN 'closed'
                        ELSE o.status
                    END,
                    o.paid_at = IF(ec.paid_at IS NOT NULL AND ec.paid_at <> '0000-00-00 00:00:00' AND ec.status IN ('shipped','delivered','completed'), ec.paid_at, o.paid_at),
                    o.shipping_amount = {$shipExpr},
                    o.base_shipping_amount = {$shipExpr},
                    o.shipping_amount_incl_tax = {$shipExpr},
                    o.base_shipping_amount_incl_tax = {$shipExpr},
                    o.cod_fee = {$codFeeExpr},
                    o.base_cod_fee = {$codFeeExpr},
                    o.grand_total_invoiced = IF(ec.status IN ('shipped','delivered','completed'), o.grand_total, o.grand_total_invoiced),
                    o.base_grand_total_invoiced = IF(ec.status IN ('shipped','delivered','completed'), o.base_grand_total, o.base_grand_total_invoiced),
                    o.sub_total_invoiced = IF(ec.status IN ('shipped','delivered','completed'), o.sub_total, o.sub_total_invoiced),
                    o.base_sub_total_invoiced = IF(ec.status IN ('shipped','delivered','completed'), o.base_sub_total, o.base_sub_total_invoiced),
                    o.updated_at = COALESCE(ec.updated_at, NOW())
                WHERE ec.store_id = ?",
                [$pref, $storeId]);

            DB::statement("
                UPDATE `{$this->bg}`.order_items oi
                JOIN `{$this->bg}`.orders o ON o.id = oi.order_id
                JOIN `{$this->stage}`.ecommerce_cart ec ON o.increment_id = CONCAT(?, '-', ec.id)
                SET oi.qty_invoiced = IF(ec.status IN ('shipped','delivered','completed'), oi.qty_ordered, oi.qty_invoiced)
                WHERE ec.store_id = ?",
                [$pref, $storeId]);

            DB::statement("
                INSERT INTO `{$this->bg}`.order_items
                    (order_id, sku, type, name, qty_ordered, qty_shipped, qty_invoiced,
                     qty_canceled, qty_refunded, price, base_price, total, base_total,
                     tax_amount, base_tax_amount, discount_amount, base_discount_amount,
                     price_incl_tax, base_price_incl_tax, total_incl_tax, base_total_incl_tax,
                     product_id, product_type, created_at, updated_at)
                SELECT o.id,
                       CONCAT(?, '-', eci.product_id),
                       'simple',
                       COALESCE(p.sku, CONCAT(?, '-', eci.product_id)),
                       eci.quantity, 0,
                       IF(ec.status IN ('shipped','delivered','completed'), eci.quantity, 0),
                       0, 0,
                       eci.unit_price, eci.unit_price,
                       eci.unit_price * eci.quantity, eci.unit_price * eci.quantity,
                       0, 0, 0, 0,
                       eci.unit_price, eci.unit_price,
                       eci.unit_price * eci.quantity, eci.unit_price * eci.quantity,
                       p.id, 'Webkul\\\\Product\\\\Models\\\\Product',
                       COALESCE(eci.updated_at, NOW()), COALESCE(eci.updated_at, NOW())
                  FROM `{$this->stage}`.ecommerce_cart_item eci
                  JOIN `{$this->stage}`.ecommerce_cart ec ON ec.id = eci.cart_id
                  JOIN `{$this->bg}`.orders o ON o.increment_id = CONCAT(?, '-', ec.id)
                  LEFT JOIN `{$this->bg}`.products p ON p.sku = CONCAT(?, '-', eci.product_id)
                  LEFT JOIN `{$this->bg}`.order_items oi
                         ON oi.order_id = o.id AND oi.sku = CONCAT(?, '-', eci.product_id)
                 WHERE ec.store_id = ? AND oi.id IS NULL",
                [$spref, $spref, $pref, $spref, $spref, $storeId]);

            // product_ordered_inventories holds OPEN reservations (Bagisto semantics):
            // qty = qty_ordered − qty_shipped − qty_canceled − qty_refunded, only for
            // orders in pending/pending_payment/processing/fraud AND not yet
            // label-printed. Older ChatPion-import logic used SUM(qty_invoiced) as a
            // lifetime sales counter, which collided with isSaleable() and made
            // hundreds of SKUs unbuyable. See feedback_ordered_inventories_dual_semantics.
            //
            // Truth source: orders + order_items. We DELETE this channel's POI rows and
            // re-INSERT only the ones backed by real open orders. Use ON DUPLICATE KEY
            // UPDATE to be safe against the unique key (product_id, channel_id).
            DB::statement("DELETE FROM `{$this->bg}`.product_ordered_inventories WHERE channel_id = ?", [$ch]);

            DB::statement("
                INSERT INTO `{$this->bg}`.product_ordered_inventories (product_id, channel_id, qty)
                SELECT oi.product_id, ?, SUM(
                    oi.qty_ordered
                    - COALESCE(oi.qty_shipped, 0)
                    - COALESCE(oi.qty_canceled, 0)
                    - COALESCE(oi.qty_refunded, 0)
                ) AS open_qty
                  FROM `{$this->bg}`.order_items oi
                  JOIN `{$this->bg}`.orders o ON o.id = oi.order_id
                 WHERE o.channel_id = ?
                   AND o.status IN ('pending','pending_payment','processing','fraud')
                   AND (o.fujiba_label_printed IS NULL OR o.fujiba_label_printed = 0)
                   AND oi.parent_id IS NULL
                 GROUP BY oi.product_id
                HAVING open_qty > 0
                ON DUPLICATE KEY UPDATE qty = VALUES(qty)",
                [$ch, $ch]);

            // Normalize ChatPion payment_method strings to Bagisto's internal codes so
            // the admin view can match `cashondelivery` / `moneytransfer` for COD-fee
            // display, status switches, and invoice generation.
            $methodExpr = "CASE
                WHEN ec.payment_method LIKE '%Cash on Delivery%' THEN 'cashondelivery'
                WHEN ec.payment_method = 'Manual' OR ec.payment_method = '' OR ec.payment_method IS NULL THEN 'moneytransfer'
                ELSE ec.payment_method
            END";
            $methodTitleExpr = "CASE
                WHEN ec.payment_method LIKE '%Cash on Delivery%' THEN '代金引換'
                WHEN ec.payment_method = 'Manual' OR ec.payment_method = '' OR ec.payment_method IS NULL THEN '銀行振込'
                ELSE ec.payment_method
            END";

            DB::statement("
                INSERT INTO `{$this->bg}`.order_payment
                    (order_id, method, method_title, created_at, updated_at)
                SELECT o.id,
                       {$methodExpr},
                       {$methodTitleExpr},
                       COALESCE(ec.ordered_at, NOW()), COALESCE(ec.updated_at, NOW())
                  FROM `{$this->stage}`.ecommerce_cart ec
                  JOIN `{$this->bg}`.orders o ON o.increment_id = CONCAT(?, '-', ec.id)
                  LEFT JOIN `{$this->bg}`.order_payment op ON op.order_id = o.id
                 WHERE ec.store_id = ? AND op.id IS NULL",
                [$pref, $storeId]);

            // Refresh existing order_payment rows so historic syncs pick up the new codes.
            DB::statement("
                UPDATE `{$this->bg}`.order_payment op
                JOIN `{$this->bg}`.orders o ON o.id = op.order_id
                JOIN `{$this->stage}`.ecommerce_cart ec ON o.increment_id = CONCAT(?, '-', ec.id)
                SET op.method = {$methodExpr},
                    op.method_title = {$methodTitleExpr}
                WHERE ec.store_id = ?",
                [$pref, $storeId]);

            foreach (['order_billing' => 'bill', 'order_shipping' => 'buyer'] as $type => $p) {
                // Store pickup orders: skip the order_shipping address insert.
                $pickupSkip = ($type === 'order_shipping')
                    ? "AND COALESCE(ec.store_pickup, '0') <> '1'"
                    : '';
                DB::statement("
                    INSERT INTO `{$this->bg}`.addresses
                        (address_type, order_id, first_name, last_name, email, phone,
                         country, state, city, postcode, address, created_at, updated_at)
                    SELECT ?, o.id,
                           COALESCE(NULLIF(ec.{$p}_first_name,''), NULLIF(cu.first_name,'')),
                           COALESCE(NULLIF(ec.{$p}_last_name,''),  NULLIF(cu.last_name,'')),
                           COALESCE(NULLIF(ec.{$p}_email,''),      NULLIF(cu.email,'')),
                           COALESCE(NULLIF(ec.{$p}_mobile,''),     NULLIF(cu.phone,'')),
                           ec.{$p}_country, ec.{$p}_state, ec.{$p}_city,
                           ec.{$p}_zip, ec.{$p}_address,
                           COALESCE(ec.ordered_at, NOW()), COALESCE(ec.updated_at, NOW())
                      FROM `{$this->stage}`.ecommerce_cart ec
                      JOIN `{$this->bg}`.orders o ON o.increment_id = CONCAT(?, '-', ec.id)
                      LEFT JOIN `{$this->bg}`.customers cu
                             ON cu.chatpion_subscribe_id = ec.subscriber_id AND cu.channel_id = ?
                      LEFT JOIN `{$this->bg}`.addresses oa
                             ON oa.order_id = o.id AND oa.address_type = ?
                     WHERE ec.store_id = ? AND oa.id IS NULL {$pickupSkip}",
                    [$type, $pref, $ch, $type, $storeId]);
            }
        });

        $this->info('  orders: applied.');
    }
}
