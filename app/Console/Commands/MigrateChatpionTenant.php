<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateChatpionTenant extends Command
{
    protected $signature = 'chatpion:migrate
        {--tenant-id= : Bagisto tenant UUID}
        {--tenant-db= : Tenant database name}
        {--source-db=chatpion_import : Chatpion source database}
        {--store-kimono= : Chatpion store_id for kimono channel}
        {--store-mmfood= : Chatpion store_id for myanmar-food channel}
        {--channel-kimono=1 : Bagisto channel_id for kimono}
        {--channel-mmfood=2 : Bagisto channel_id for myanmar-food}
        {--page-kimono= : Facebook page_id for kimono}
        {--page-mmfood= : Facebook page_id for myanmar-food}
        {--api-key= : Chatpion API key}
        {--kimono-code=default : Channel code for kimono}
        {--mmfood-code=myanmar-food : Channel code for myanmar-food}';

    protected $description = 'Migrate Chatpion ecommerce data into a Bagisto tenant database';

    protected string $tenantDb;
    protected string $sourceDb;
    protected array $channels;
    protected array $statusMap = [
        'pending'   => 'pending',
        'approved'  => 'processing',
        'shipped'   => 'processing',
        'delivered' => 'processing',
        'completed' => 'completed',
        'rejected'  => 'canceled',
    ];
    protected array $summary = [];

    public function handle(): int
    {
        $tenantId  = $this->option('tenant-id') ?? $this->ask('Tenant UUID');
        $this->tenantDb = $this->option('tenant-db') ?? $this->ask('Tenant database name');
        $this->sourceDb = $this->option('source-db');

        $storeKimono = $this->option('store-kimono') ?? $this->ask('Chatpion store_id for kimono');
        $storeMmfood = $this->option('store-mmfood') ?? $this->ask('Chatpion store_id for myanmar-food');
        $channelKimono = (int) $this->option('channel-kimono');
        $channelMmfood = (int) $this->option('channel-mmfood');
        $kimonoCode = $this->option('kimono-code');
        $mmfoodCode = $this->option('mmfood-code');

        $this->channels = [
            (int) $storeKimono => [
                'id' => $channelKimono, 'code' => $kimonoCode,
                'sku_prefix' => 'KIMONO-', 'inc_prefix' => 'KM-',
                'name' => 'Kimono Store', 'url_suffix' => '-k',
            ],
            (int) $storeMmfood => [
                'id' => $channelMmfood, 'code' => $mmfoodCode,
                'sku_prefix' => 'MMFOOD-', 'inc_prefix' => 'MF-',
                'name' => 'Myanmar Food Store', 'url_suffix' => '-m',
            ],
        ];

        $storeIds = array_keys($this->channels);

        // Verify databases exist
        try {
            DB::connection('mysql')->select("SELECT 1 FROM `{$this->sourceDb}`.ecommerce_product LIMIT 1");
            DB::connection('mysql')->select("SELECT 1 FROM `{$this->tenantDb}`.channels LIMIT 1");
        } catch (\Throwable $e) {
            $this->error("Database check failed: " . $e->getMessage());
            return 1;
        }

        $this->info("Starting migration: {$this->sourceDb} → {$this->tenantDb}");
        $this->info("Store IDs: " . implode(', ', $storeIds));
        $this->newLine();

        $pdo = DB::connection('mysql')->getPdo();

        $this->migrateCustomers($pdo, $storeIds);
        $this->migrateAddresses($pdo, $storeIds);
        $this->migrateProducts($pdo, $storeIds);
        $this->migrateOrders($pdo, $storeIds);
        $this->migrateOrderItems($pdo, $storeIds);
        $this->updateOrderTotals($pdo);
        $this->migrateOrderPayments($pdo, $storeIds);

        // Register page mapping
        if ($this->option('page-kimono') || $this->option('page-mmfood')) {
            $this->registerPageMapping($tenantId, $storeKimono, $storeMmfood);
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('       MIGRATION SUMMARY');
        $this->info('========================================');
        foreach ($this->summary as $key => $val) {
            $this->line(sprintf("  %-20s %s", $key . ':', $val));
        }
        $this->info('========================================');

        return 0;
    }

    protected function migrateCustomers(\PDO $pdo, array $storeIds): void
    {
        $this->info('=== Section 1: CUSTOMERS ===');
        $ids = implode(',', $storeIds);
        $count = 0;

        $pdo->exec("SET SESSION sql_mode = REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', '')");

        foreach ($this->channels as $storeId => $ch) {
            $pdo->exec("
                INSERT INTO `{$this->tenantDb}`.customers
                    (first_name, last_name, email, phone, status, password,
                     customer_group_id, channel_id, is_verified,
                     subscribed_to_news_letter, token, created_at, updated_at)
                SELECT
                    COALESCE(NULLIF(TRIM(s.first_name),''), 'Customer'),
                    COALESCE(NULLIF(TRIM(s.last_name),''), '-'),
                    CASE WHEN s.email IS NOT NULL AND s.email != '' THEN LOWER(TRIM(s.email))
                         ELSE CONCAT(s.subscribe_id, '@messenger.local') END,
                    NULLIF(TRIM(s.phone_number),''),
                    1, NULL, 2, {$ch['id']}, 1, 0,
                    s.subscribe_id, MIN(ec.updated_at), NOW()
                FROM `{$this->sourceDb}`.ecommerce_cart ec
                INNER JOIN `{$this->sourceDb}`.messenger_bot_subscriber s
                    ON s.subscribe_id = ec.subscriber_id
                WHERE ec.store_id = {$storeId} AND ec.action_type = 'checkout'
                GROUP BY s.subscribe_id
                ON DUPLICATE KEY UPDATE token = VALUES(token)
            ");
            $count += $pdo->query("SELECT ROW_COUNT()")->fetchColumn();
        }

        $this->line("Section 1: {$count} customers inserted");
        $this->summary['customers'] = $count;
    }

    protected function migrateAddresses(\PDO $pdo, array $storeIds): void
    {
        $this->info('=== Section 2: ADDRESSES ===');

        $count = (int) $pdo->query("
            INSERT INTO `{$this->tenantDb}`.addresses
                (address_type, customer_id, first_name, last_name,
                 address, city, state, country, postcode, phone,
                 default_address, created_at, updated_at)
            SELECT
                'customer', c.id,
                COALESCE(NULLIF(TRIM(a.first_name),''), c.first_name),
                COALESCE(NULLIF(TRIM(a.last_name),''), c.last_name),
                COALESCE(NULLIF(TRIM(a.address),''), '-'),
                COALESCE(NULLIF(TRIM(a.city),''), '-'),
                COALESCE(NULLIF(TRIM(a.state),''), '-'),
                CASE WHEN a.country='' OR a.country IS NULL THEN 'JP' ELSE UPPER(a.country) END,
                COALESCE(NULLIF(TRIM(a.zip),''), '000-0000'),
                NULLIF(TRIM(a.mobile),''),
                a.is_default, NOW(), NOW()
            FROM `{$this->sourceDb}`.ecommerce_cart_address_saved a
            INNER JOIN `{$this->sourceDb}`.messenger_bot_subscriber s
                ON s.subscribe_id = a.subscriber_id
            INNER JOIN `{$this->tenantDb}`.customers c
                ON c.token = s.subscribe_id
            WHERE c.channel_id IN (" . implode(',', array_column($this->channels, 'id')) . ")
        ")->rowCount();

        $this->line("Section 2: {$count} addresses inserted");
        $this->summary['addresses'] = $count;
    }

    protected function migrateProducts(\PDO $pdo, array $storeIds): void
    {
        $this->info('=== Section 3: PRODUCTS ===');
        $count = 0;
        $ids = implode(',', $storeIds);
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->query("
            SELECT id, store_id, product_name, original_price, sell_price,
                   stock_item, status, product_description, product_weight
            FROM `{$this->sourceDb}`.ecommerce_product
            WHERE deleted = '0' AND store_id IN ({$ids})
        ");

        $insertProd = $pdo->prepare("INSERT IGNORE INTO `{$this->tenantDb}`.products (sku, type, attribute_family_id, created_at, updated_at) VALUES (?, 'simple', 1, ?, ?)");
        $insertFlat = $pdo->prepare("INSERT INTO `{$this->tenantDb}`.product_flat (product_id, sku, type, name, description, url_key, status, price, special_price, weight, locale, channel, attribute_family_id, visible_individually, new, featured, created_at, updated_at) VALUES (?,?,'simple',?,?,?,?,?,?,?,'en',?,1,1,0,0,?,?)");
        $insertInv = $pdo->prepare("INSERT INTO `{$this->tenantDb}`.product_inventories (product_id, qty, inventory_source_id, vendor_id) VALUES (?,?,1,0)");
        $insertChan = $pdo->prepare("INSERT IGNORE INTO `{$this->tenantDb}`.product_channels (product_id, channel_id) VALUES (?,?)");
        // PAVs required by storefront searchFromDatabase: visible_individually (attr=7, no scope) + status (attr=8, per channel)
        $insertPavVisible = $pdo->prepare("INSERT IGNORE INTO `{$this->tenantDb}`.product_attribute_values (product_id, attribute_id, channel, locale, boolean_value, unique_id) VALUES (?,7,NULL,NULL,1,?)");
        $insertPavStatus  = $pdo->prepare("INSERT IGNORE INTO `{$this->tenantDb}`.product_attribute_values (product_id, attribute_id, channel, locale, boolean_value, unique_id) VALUES (?,8,?,NULL,?,?)");

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $ch = $this->channels[$row['store_id']];
            $sku = $ch['sku_prefix'] . $row['id'];
            $urlKey = strtolower(trim($row['product_name']));
            $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
            $urlKey = trim($urlKey, '-') . $ch['url_suffix'] . $row['id'];

            $insertProd->execute([$sku, $now, $now]);
            if ($insertProd->rowCount() === 0) continue;
            $productId = $pdo->lastInsertId();

            $price = (float) $row['original_price'];
            $sell = (float) $row['sell_price'];
            $special = ($sell > 0 && $sell < $price) ? $sell : null;
            $status = ($row['status'] == 1 || $row['status'] === 'active') ? 1 : 0;
            $weight = ((float) ($row['product_weight'] ?? 0)) / 1000;

            $insertFlat->execute([$productId, $sku, $row['product_name'], $row['product_description'] ?? '', $urlKey, $status, $price, $special, $weight, $ch['code'], $now, $now]);
            $insertInv->execute([$productId, (int) ($row['stock_item'] ?? 0)]);
            $insertChan->execute([$productId, $ch['id']]);
            $insertPavVisible->execute([$productId, $productId.'|7']);
            $insertPavStatus->execute([$productId, $ch['code'], $status, $ch['code'].'|'.$productId.'|8']);
            $count++;
        }

        $this->line("Section 3: {$count} products inserted");
        $this->summary['products'] = $count;
    }

    protected function migrateOrders(\PDO $pdo, array $storeIds): void
    {
        $this->info('=== Section 4: ORDERS ===');
        $ids = implode(',', $storeIds);
        $count = 0;
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->query("
            SELECT c.id AS cart_id, c.store_id, c.subscriber_id, c.payment_amount,
                   c.status AS cart_status, c.currency, c.ordered_at,
                   c.buyer_email, c.buyer_first_name, c.buyer_last_name
            FROM `{$this->sourceDb}`.ecommerce_cart c
            WHERE c.action_type = 'checkout' AND c.store_id IN ({$ids})
        ");

        $findCust = $pdo->prepare("SELECT id, email FROM `{$this->tenantDb}`.customers WHERE token = ? AND channel_id = ? LIMIT 1");

        $insertOrder = $pdo->prepare("
            INSERT IGNORE INTO `{$this->tenantDb}`.orders
                (increment_id, status, channel_name, is_guest, customer_email,
                 customer_first_name, customer_last_name,
                 base_currency_code, channel_currency_code, order_currency_code,
                 grand_total, base_grand_total, sub_total, base_sub_total,
                 grand_total_invoiced, base_grand_total_invoiced,
                 sub_total_invoiced, base_sub_total_invoiced,
                 customer_id, customer_type, channel_id, channel_type,
                 created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,'JPY','JPY',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $ch = $this->channels[$row['store_id']];
            $incId = $ch['inc_prefix'] . $row['cart_id'];
            $rawStatus = strtolower(trim($row['cart_status'] ?? 'pending'));
            $status = $this->statusMap[$rawStatus] ?? 'pending';
            $currency = !empty($row['currency']) ? strtoupper($row['currency']) : 'JPY';
            $total = (float) ($row['payment_amount'] ?? 0);

            $email = !empty($row['buyer_email']) ? $row['buyer_email'] : null;
            $firstName = !empty($row['buyer_first_name']) ? $row['buyer_first_name'] : 'Guest';
            $lastName = !empty($row['buyer_last_name']) ? $row['buyer_last_name'] : '';

            $customerId = null; $isGuest = 1; $customerType = null;
            if (!empty($row['subscriber_id'])) {
                $findCust->execute([$row['subscriber_id'], $ch['id']]);
                $cust = $findCust->fetch(\PDO::FETCH_ASSOC);
                if ($cust) {
                    $customerId = $cust['id'];
                    $isGuest = 0;
                    $customerType = 'Webkul\\Customer\\Models\\Customer';
                    $email = $email ?: $cust['email'];
                }
            }

            $invoiced = in_array($status, ['processing', 'completed']) ? $total : 0;

            $insertOrder->execute([
                $incId, $status, $ch['name'], $isGuest, $email, $firstName, $lastName,
                $currency, $total, $total, $total, $total,
                $invoiced, $invoiced, $invoiced, $invoiced,
                $customerId, $customerType, $ch['id'],
                'Webkul\\Core\\Models\\Channel',
                $row['ordered_at'] ?? $now, $now,
            ]);
            if ($insertOrder->rowCount() > 0) $count++;
        }

        $this->line("Section 4: {$count} orders inserted");
        $this->summary['orders'] = $count;
    }

    protected function migrateOrderItems(\PDO $pdo, array $storeIds): void
    {
        $this->info('=== Section 5: ORDER ITEMS ===');
        $ids = implode(',', $storeIds);
        $count = 0;
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->query("
            SELECT ci.cart_id, ci.product_id AS src_pid, ci.unit_price, ci.quantity,
                   c.store_id, p.product_name
            FROM `{$this->sourceDb}`.ecommerce_cart_item ci
            JOIN `{$this->sourceDb}`.ecommerce_cart c ON c.id = ci.cart_id
            LEFT JOIN `{$this->sourceDb}`.ecommerce_product p ON p.id = ci.product_id
            WHERE c.action_type = 'checkout' AND c.store_id IN ({$ids})
        ");

        $findOrder = $pdo->prepare("SELECT id FROM `{$this->tenantDb}`.orders WHERE increment_id = ? LIMIT 1");
        $findProd = $pdo->prepare("SELECT id FROM `{$this->tenantDb}`.products WHERE sku = ? LIMIT 1");
        $checkItem = $pdo->prepare("SELECT id FROM `{$this->tenantDb}`.order_items WHERE order_id = ? AND sku = ? LIMIT 1");

        $insertItem = $pdo->prepare("
            INSERT INTO `{$this->tenantDb}`.order_items
                (sku, type, name, qty_ordered, qty_shipped, qty_invoiced, qty_canceled, qty_refunded,
                 price, base_price, total, base_total,
                 price_incl_tax, base_price_incl_tax, total_incl_tax, base_total_incl_tax,
                 product_id, product_type, order_id, created_at, updated_at)
            VALUES (?,'simple',?,?,0,?,0,0,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $ch = $this->channels[$row['store_id']];
            $incId = $ch['inc_prefix'] . $row['cart_id'];

            $findOrder->execute([$incId]);
            $order = $findOrder->fetch(\PDO::FETCH_ASSOC);
            if (!$order) continue;

            $sku = $ch['sku_prefix'] . $row['src_pid'];
            $checkItem->execute([$order['id'], $sku]);
            if ($checkItem->fetch()) continue;

            $findProd->execute([$sku]);
            $prod = $findProd->fetch(\PDO::FETCH_ASSOC);
            $productId = $prod ? $prod['id'] : null;

            $qty = max(1, (int) $row['quantity']);
            $price = (float) $row['unit_price'];
            $total = $price * $qty;

            // Check if order is invoiced (processing/completed) for qty_invoiced
            $orderStatus = DB::connection('mysql')
                ->select("SELECT status FROM `{$this->tenantDb}`.orders WHERE id = ?", [$order['id']]);
            $isInvoiced = in_array($orderStatus[0]->status ?? '', ['processing', 'completed']);
            $qtyInvoiced = $isInvoiced ? $qty : 0;

            $insertItem->execute([
                $sku, $row['product_name'] ?? 'Product', $qty, $qtyInvoiced,
                $price, $price, $total, $total,
                $price, $price, $total, $total,
                $productId, 'Webkul\\Product\\Models\\Product',
                $order['id'], $now, $now,
            ]);
            $count++;
        }

        $this->line("Section 5: {$count} order items inserted");
        $this->summary['order_items'] = $count;
    }

    protected function updateOrderTotals(\PDO $pdo): void
    {
        $this->info('=== Section 6: ORDER TOTALS ===');

        $pdo->exec("
            UPDATE `{$this->tenantDb}`.orders o SET
                total_item_count = (SELECT COUNT(*) FROM `{$this->tenantDb}`.order_items oi WHERE oi.order_id = o.id),
                total_qty_ordered = (SELECT COALESCE(SUM(oi.qty_ordered),0) FROM `{$this->tenantDb}`.order_items oi WHERE oi.order_id = o.id)
            WHERE o.increment_id LIKE 'KM-%' OR o.increment_id LIKE 'MF-%'
        ");

        $updated = $pdo->query("SELECT COUNT(*) FROM `{$this->tenantDb}`.orders WHERE increment_id LIKE 'KM-%' OR increment_id LIKE 'MF-%'")->fetchColumn();
        $this->line("Section 6: {$updated} orders updated");
        $this->summary['orders_updated'] = $updated;
    }

    protected function migrateOrderPayments(\PDO $pdo, array $storeIds): void
    {
        $this->info('=== Section 7: ORDER PAYMENTS ===');
        $ids = implode(',', $storeIds);
        $count = 0;
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->query("
            SELECT c.id AS cart_id, c.store_id, c.payment_method
            FROM `{$this->sourceDb}`.ecommerce_cart c
            WHERE c.action_type = 'checkout' AND c.store_id IN ({$ids})
        ");

        $findOrder = $pdo->prepare("SELECT id FROM `{$this->tenantDb}`.orders WHERE increment_id = ? LIMIT 1");
        $checkPay = $pdo->prepare("SELECT id FROM `{$this->tenantDb}`.order_payment WHERE order_id = ? LIMIT 1");
        $insertPay = $pdo->prepare("INSERT INTO `{$this->tenantDb}`.order_payment (order_id, method, method_title, created_at, updated_at) VALUES (?,?,?,?,?)");

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $ch = $this->channels[$row['store_id']];
            $incId = $ch['inc_prefix'] . $row['cart_id'];

            $findOrder->execute([$incId]);
            $order = $findOrder->fetch(\PDO::FETCH_ASSOC);
            if (!$order) continue;

            $checkPay->execute([$order['id']]);
            if ($checkPay->fetch()) continue;

            $pm = strtolower(trim($row['payment_method'] ?? ''));
            if (str_contains($pm, 'paypal')) $method = 'paypal_standard';
            elseif (str_contains($pm, 'stripe')) $method = 'stripe';
            elseif (str_contains($pm, 'cash') || str_contains($pm, 'cod')) $method = 'cashondelivery';
            elseif (str_contains($pm, 'bank') || str_contains($pm, 'manual') || str_contains($pm, 'transfer')) $method = 'moneytransfer';
            else $method = 'free';

            $insertPay->execute([$order['id'], $method, $row['payment_method'] ?? $method, $now, $now]);
            $count++;
        }

        $this->line("Section 7: {$count} payments inserted");
        $this->summary['payments'] = $count;
    }

    protected function registerPageMapping(string $tenantId, $storeKimono, $storeMmfood): void
    {
        $this->info('=== Section 8: PAGE MAPPING ===');

        $apiKey = $this->option('api-key');
        $rows = [];

        if ($pageKimono = $this->option('page-kimono')) {
            $ch = $this->channels[(int) $storeKimono];
            $rows[] = [
                'tenant_id'        => $tenantId,
                'tenant_db'        => $this->tenantDb,
                'facebook_page_id' => $pageKimono,
                'channel_id'       => $ch['id'],
                'channel_code'     => $ch['code'],
                'chatpion_api_key' => $apiKey,
                'chatpion_store_id'=> (int) $storeKimono,
                'is_active'        => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        if ($pageMmfood = $this->option('page-mmfood')) {
            $ch = $this->channels[(int) $storeMmfood];
            $rows[] = [
                'tenant_id'        => $tenantId,
                'tenant_db'        => $this->tenantDb,
                'facebook_page_id' => $pageMmfood,
                'channel_id'       => $ch['id'],
                'channel_code'     => $ch['code'],
                'chatpion_api_key' => $apiKey,
                'chatpion_store_id'=> (int) $storeMmfood,
                'is_active'        => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        foreach ($rows as $row) {
            DB::connection('mysql')
                ->table('messenger_page_channel_map')
                ->updateOrInsert(
                    ['tenant_id' => $row['tenant_id'], 'facebook_page_id' => $row['facebook_page_id']],
                    $row
                );
        }

        $this->line("Section 8: " . count($rows) . " page mappings registered");
        $this->summary['page_mappings'] = count($rows);
    }
}
