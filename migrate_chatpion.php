<?php
/**
 * Chatpion → Bagisto Migration Script
 *
 * Correct mapping:
 *   store_id 9  / page_id 69 → channel_id 2 (myanmar-food)  MMFOOD-  MF-
 *   store_id 10 / page_id 71 → channel_id 1 (kimono)        KIMONO-  KM-
 */

$host     = '127.0.0.1';
$port     = 3306;
$user     = 'bagisto';
$pass     = 'Bagisto2024';
$tenantDb = 'tenant_1bd1181f-fcd5-4238-a12d-a207d6ea4006';
$sourceDb = 'chatpion_import';

$now = date('Y-m-d H:i:s');

// Channel mapping by store_id
$channelsByStore = [
    9  => ['id' => 2, 'code' => 'myanmar-food',  'sku_prefix' => 'MMFOOD-', 'inc_prefix' => 'MF-', 'name' => 'Myanmar Food Store', 'url_suffix' => '-m'],
    10 => ['id' => 1, 'code' => 'kimono',         'sku_prefix' => 'KIMONO-', 'inc_prefix' => 'KM-', 'name' => 'Kimono Store',       'url_suffix' => '-k'],
];

// Channel mapping by page_table_id (for subscribers — links to facebook_rx_fb_page_info.id)
$channelsByPage = [
    69 => $channelsByStore[9],   // page_table_id 69 = store 9 = myanmar-food
    71 => $channelsByStore[10],  // page_table_id 71 = store 10 = kimono
];

// Status mapping
$statusMap = [
    'pending'   => 'pending',
    'approved'  => 'processing',
    'shipped'   => 'processing',
    'delivered' => 'processing',
    'completed' => 'completed',
    'rejected'  => 'canceled',
];

$summary = [];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Verify both databases exist
    $pdo->query("USE `$sourceDb`");
    $pdo->query("USE `$tenantDb`");

    // =========================================================================
    // SECTION 1: CUSTOMERS
    // =========================================================================
    echo "=== Section 1: CUSTOMERS ===\n";
    $pdo->beginTransaction();
    try {
        $custCount = 0;

        $stmt = $pdo->query("
            SELECT s.subscribe_id, s.page_table_id, s.first_name, s.last_name, s.email, s.phone_number
            FROM `$sourceDb`.messenger_bot_subscriber s
            WHERE s.page_table_id IN (69, 71)
        ");

        $insertCust = $pdo->prepare("
            INSERT IGNORE INTO `$tenantDb`.customers
                (first_name, last_name, email, phone, password, customer_group_id, channel_id, is_verified, token, status, created_at, updated_at)
            VALUES
                (:first_name, :last_name, :email, :phone, NULL, 2, :channel_id, 1, :token, 1, :now, :now)
        ");

        while ($row = $stmt->fetch()) {
            $ch = $channelsByPage[$row['page_table_id']];
            $email = trim($row['email'] ?? '');
            if ($email === '') {
                $email = $row['subscribe_id'] . '@messenger.local';
            }

            $insertCust->execute([
                ':first_name'  => $row['first_name'] ?: 'Customer',
                ':last_name'   => $row['last_name'] ?: '',
                ':email'       => $email,
                ':phone'       => $row['phone_number'] ?? null,
                ':channel_id'  => $ch['id'],
                ':token'       => (string) $row['subscribe_id'],
                ':now'         => $now,
            ]);
            if ($insertCust->rowCount() > 0) $custCount++;
        }

        $pdo->commit();
        echo "Section 1: $custCount customers inserted\n";
        $summary['customers'] = $custCount;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Section 1 FAILED: " . $e->getMessage() . "\n";
        $summary['customers'] = 'FAILED';
    }

    // =========================================================================
    // SECTION 2: CUSTOMER ADDRESSES
    // =========================================================================
    echo "\n=== Section 2: CUSTOMER ADDRESSES ===\n";
    $pdo->beginTransaction();
    try {
        $addrCount = 0;

        $stmt = $pdo->query("
            SELECT
                a.id AS addr_id,
                a.subscriber_id,
                a.first_name,
                a.last_name,
                a.email AS addr_email,
                a.address,
                a.city,
                a.state,
                a.country,
                a.zip,
                a.mobile,
                a.is_default,
                s.page_table_id
            FROM `$sourceDb`.ecommerce_cart_address_saved a
            JOIN `$sourceDb`.messenger_bot_subscriber s ON s.subscribe_id = a.subscriber_id
            WHERE s.page_table_id IN (69, 71)
        ");

        $findCust = $pdo->prepare("
            SELECT id FROM `$tenantDb`.customers WHERE token = :token LIMIT 1
        ");

        $insertAddr = $pdo->prepare("
            INSERT INTO `$tenantDb`.addresses
                (address_type, customer_id, first_name, last_name, address, city, state, country, postcode, phone, default_address, created_at, updated_at)
            VALUES
                ('customer', :customer_id, :first_name, :last_name, :address, :city, :state, :country, :postcode, :phone, :default_address, :now, :now)
        ");

        while ($row = $stmt->fetch()) {
            $findCust->execute([':token' => (string) $row['subscriber_id']]);
            $customer = $findCust->fetch();
            if (!$customer) continue;

            $country = trim($row['country'] ?? '');
            $country = $country === '' ? 'JP' : strtoupper($country);

            $insertAddr->execute([
                ':customer_id'     => $customer['id'],
                ':first_name'      => $row['first_name'] ?: 'Customer',
                ':last_name'       => $row['last_name'] ?: '',
                ':address'         => $row['address'] ?: '',
                ':city'            => $row['city'] ?: '',
                ':state'           => $row['state'] ?: '',
                ':country'         => $country,
                ':postcode'        => $row['zip'] ?: '',
                ':phone'           => $row['mobile'] ?: '',
                ':default_address' => $row['is_default'] ? 1 : 0,
                ':now'             => $now,
            ]);
            $addrCount++;
        }

        $pdo->commit();
        echo "Section 2: $addrCount addresses inserted\n";
        $summary['addresses'] = $addrCount;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Section 2 FAILED: " . $e->getMessage() . "\n";
        $summary['addresses'] = 'FAILED';
    }

    // =========================================================================
    // SECTION 3: PRODUCTS
    // =========================================================================
    echo "\n=== Section 3: PRODUCTS ===\n";
    $pdo->beginTransaction();
    try {
        $prodCount = 0;

        $stmt = $pdo->query("
            SELECT id, store_id, product_name, original_price, sell_price, stock_item, status, product_description
            FROM `$sourceDb`.ecommerce_product
            WHERE deleted = '0'
              AND store_id IN (9, 10)
        ");

        $insertProd = $pdo->prepare("
            INSERT IGNORE INTO `$tenantDb`.products (sku, type, attribute_family_id, created_at, updated_at)
            VALUES (:sku, 'simple', 1, :now, :now)
        ");

        $insertFlat = $pdo->prepare("
            INSERT INTO `$tenantDb`.product_flat
                (product_id, sku, type, name, description, url_key, status, price, special_price, weight, locale, channel, attribute_family_id, visible_individually, new, featured, created_at, updated_at)
            VALUES
                (:product_id, :sku, 'simple', :name, :description, :url_key, :status, :price, :special_price, 0, 'en', :channel, 1, 1, 0, 0, :now, :now)
        ");

        $insertInv = $pdo->prepare("
            INSERT INTO `$tenantDb`.product_inventories (product_id, qty, inventory_source_id, vendor_id)
            VALUES (:product_id, :qty, 1, 0)
        ");

        $insertChan = $pdo->prepare("
            INSERT IGNORE INTO `$tenantDb`.product_channels (product_id, channel_id)
            VALUES (:product_id, :channel_id)
        ");

        while ($row = $stmt->fetch()) {
            $ch = $channelsByStore[$row['store_id']];
            $sku = $ch['sku_prefix'] . $row['id'];

            // Generate url_key
            $urlKey = strtolower(trim($row['product_name']));
            $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
            $urlKey = trim($urlKey, '-');
            $urlKey .= $ch['url_suffix'] . $row['id'];

            $insertProd->execute([':sku' => $sku, ':now' => $now]);
            if ($insertProd->rowCount() === 0) continue; // already exists

            $productId = $pdo->lastInsertId();

            $price = (float) $row['original_price'];
            $sellPrice = (float) $row['sell_price'];
            $specialPrice = ($sellPrice > 0 && $sellPrice < $price) ? $sellPrice : null;
            $status = ($row['status'] == 1 || $row['status'] === 'active') ? 1 : 0;

            $insertFlat->execute([
                ':product_id'    => $productId,
                ':sku'           => $sku,
                ':name'          => $row['product_name'],
                ':description'   => $row['product_description'] ?? '',
                ':url_key'       => $urlKey,
                ':status'        => $status,
                ':price'         => $price,
                ':special_price' => $specialPrice,
                ':channel'       => $ch['code'],
                ':now'           => $now,
            ]);

            $insertInv->execute([
                ':product_id' => $productId,
                ':qty'        => (int) ($row['stock_item'] ?? 0),
            ]);

            $insertChan->execute([
                ':product_id' => $productId,
                ':channel_id' => $ch['id'],
            ]);

            $prodCount++;
        }

        $pdo->commit();
        echo "Section 3: $prodCount products inserted\n";
        $summary['products'] = $prodCount;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Section 3 FAILED: " . $e->getMessage() . "\n";
        $summary['products'] = 'FAILED';
    }

    // =========================================================================
    // SECTION 4: ORDERS
    // =========================================================================
    echo "\n=== Section 4: ORDERS ===\n";
    $pdo->beginTransaction();
    try {
        $orderCount = 0;

        $stmt = $pdo->query("
            SELECT
                c.id AS cart_id,
                c.store_id,
                c.subscriber_id,
                c.payment_amount,
                c.status AS cart_status,
                c.currency,
                c.payment_method,
                c.ordered_at AS cart_created,
                c.buyer_email,
                c.buyer_first_name,
                c.buyer_last_name
            FROM `$sourceDb`.ecommerce_cart c
            WHERE c.action_type = 'checkout'
              AND c.store_id IN (9, 10)
        ");

        $findCust = $pdo->prepare("
            SELECT id, email FROM `$tenantDb`.customers WHERE token = :token LIMIT 1
        ");

        $insertOrder = $pdo->prepare("
            INSERT IGNORE INTO `$tenantDb`.orders
                (increment_id, status, channel_name, is_guest, customer_email, customer_first_name, customer_last_name,
                 base_currency_code, channel_currency_code, order_currency_code,
                 grand_total, base_grand_total, sub_total, base_sub_total,
                 customer_id, customer_type, channel_id, channel_type, created_at, updated_at)
            VALUES
                (:increment_id, :status, :channel_name, :is_guest, :email, :first_name, :last_name,
                 'JPY', 'JPY', :currency,
                 :total, :total2, :total3, :total4,
                 :customer_id, :customer_type, :channel_id, 'Webkul\\\\Core\\\\Models\\\\Channel', :created_at, :now)
        ");

        while ($row = $stmt->fetch()) {
            $ch = $channelsByStore[$row['store_id']];
            $incrementId = $ch['inc_prefix'] . $row['cart_id'];
            $rawStatus = strtolower(trim($row['cart_status'] ?? 'pending'));
            $status = $statusMap[$rawStatus] ?? 'pending';
            $currency = !empty($row['currency']) ? strtoupper($row['currency']) : 'JPY';
            $total = (float) ($row['payment_amount'] ?? 0);

            // Link customer by token = subscriber_id
            $customerId = null;
            $isGuest = 1;
            $customerType = null;
            $email = !empty($row['buyer_email']) ? $row['buyer_email'] : null;
            $firstName = !empty($row['buyer_first_name']) ? $row['buyer_first_name'] : 'Guest';
            $lastName = !empty($row['buyer_last_name']) ? $row['buyer_last_name'] : '';

            if (!empty($row['subscriber_id'])) {
                $findCust->execute([':token' => (string) $row['subscriber_id']]);
                $cust = $findCust->fetch();
                if ($cust) {
                    $customerId = $cust['id'];
                    $isGuest = 0;
                    $customerType = 'Webkul\\Customer\\Models\\Customer';
                    if (empty($email)) {
                        $email = $cust['email'];
                    }
                }
            }

            $insertOrder->execute([
                ':increment_id'  => $incrementId,
                ':status'        => $status,
                ':channel_name'  => $ch['name'],
                ':is_guest'      => $isGuest,
                ':email'         => $email,
                ':first_name'    => $firstName,
                ':last_name'     => $lastName,
                ':currency'      => $currency,
                ':total'         => $total,
                ':total2'        => $total,
                ':total3'        => $total,
                ':total4'        => $total,
                ':customer_id'   => $customerId,
                ':customer_type' => $customerType,
                ':channel_id'    => $ch['id'],
                ':created_at'    => $row['cart_created'] ?? $now,
                ':now'           => $now,
            ]);
            if ($insertOrder->rowCount() > 0) $orderCount++;
        }

        $pdo->commit();
        echo "Section 4: $orderCount orders inserted\n";
        $summary['orders'] = $orderCount;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Section 4 FAILED: " . $e->getMessage() . "\n";
        $summary['orders'] = 'FAILED';
    }

    // =========================================================================
    // SECTION 5: ORDER ITEMS
    // =========================================================================
    echo "\n=== Section 5: ORDER ITEMS ===\n";
    $pdo->beginTransaction();
    try {
        $itemCount = 0;

        $stmt = $pdo->query("
            SELECT
                ci.id AS item_id,
                ci.cart_id,
                ci.product_id AS src_product_id,
                ci.unit_price,
                ci.quantity,
                c.store_id,
                p.product_name
            FROM `$sourceDb`.ecommerce_cart_item ci
            JOIN `$sourceDb`.ecommerce_cart c ON c.id = ci.cart_id
            LEFT JOIN `$sourceDb`.ecommerce_product p ON p.id = ci.product_id
            WHERE c.action_type = 'checkout'
              AND c.store_id IN (9, 10)
        ");

        $findOrder = $pdo->prepare("
            SELECT id FROM `$tenantDb`.orders WHERE increment_id = :increment_id LIMIT 1
        ");

        $findProduct = $pdo->prepare("
            SELECT id FROM `$tenantDb`.products WHERE sku = :sku LIMIT 1
        ");

        $checkItem = $pdo->prepare("
            SELECT id FROM `$tenantDb`.order_items WHERE order_id = :order_id AND sku = :sku LIMIT 1
        ");

        $insertItem = $pdo->prepare("
            INSERT INTO `$tenantDb`.order_items
                (sku, type, name, qty_ordered, qty_shipped, qty_invoiced, qty_canceled, qty_refunded,
                 price, base_price, total, base_total,
                 price_incl_tax, base_price_incl_tax, total_incl_tax, base_total_incl_tax,
                 product_id, product_type, order_id, created_at, updated_at)
            VALUES
                (:sku, 'simple', :name, :qty, 0, 0, 0, 0,
                 :price, :price2, :total, :total2,
                 :price3, :price4, :total3, :total4,
                 :product_id, 'Webkul\\\\Product\\\\Models\\\\Product', :order_id, :now, :now)
        ");

        while ($row = $stmt->fetch()) {
            $ch = $channelsByStore[$row['store_id']];
            $incrementId = $ch['inc_prefix'] . $row['cart_id'];

            $findOrder->execute([':increment_id' => $incrementId]);
            $order = $findOrder->fetch();
            if (!$order) continue;

            $sku = $ch['sku_prefix'] . $row['src_product_id'];

            // Skip if this item already exists for this order
            $checkItem->execute([':order_id' => $order['id'], ':sku' => $sku]);
            if ($checkItem->fetch()) continue;

            $findProduct->execute([':sku' => $sku]);
            $prod = $findProduct->fetch();
            $productId = $prod ? $prod['id'] : null;

            $qty = max(1, (int) $row['quantity']);
            $price = (float) $row['unit_price'];
            $total = $price * $qty;

            $insertItem->execute([
                ':sku'        => $sku,
                ':name'       => $row['product_name'] ?? 'Product',
                ':qty'        => $qty,
                ':price'      => $price,
                ':price2'     => $price,
                ':total'      => $total,
                ':total2'     => $total,
                ':price3'     => $price,
                ':price4'     => $price,
                ':total3'     => $total,
                ':total4'     => $total,
                ':product_id' => $productId,
                ':order_id'   => $order['id'],
                ':now'        => $now,
            ]);
            $itemCount++;
        }

        $pdo->commit();
        echo "Section 5: $itemCount order items inserted\n";
        $summary['order_items'] = $itemCount;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Section 5 FAILED: " . $e->getMessage() . "\n";
        $summary['order_items'] = 'FAILED';
    }

    // =========================================================================
    // SECTION 6: UPDATE ORDER TOTALS
    // =========================================================================
    echo "\n=== Section 6: ORDER TOTALS UPDATE ===\n";
    try {
        $pdo->exec("
            UPDATE `$tenantDb`.orders o
            SET
                total_item_count = (SELECT COUNT(*) FROM `$tenantDb`.order_items oi WHERE oi.order_id = o.id),
                total_qty_ordered = (SELECT COALESCE(SUM(oi.qty_ordered), 0) FROM `$tenantDb`.order_items oi WHERE oi.order_id = o.id)
            WHERE o.increment_id LIKE 'KM-%' OR o.increment_id LIKE 'MF-%'
        ");
        $updated = $pdo->query("
            SELECT COUNT(*) AS cnt FROM `$tenantDb`.orders WHERE increment_id LIKE 'KM-%' OR increment_id LIKE 'MF-%'
        ")->fetch()['cnt'];
        echo "Section 6: $updated orders updated with item counts\n";
        $summary['orders_updated'] = $updated;
    } catch (Exception $e) {
        echo "Section 6 FAILED: " . $e->getMessage() . "\n";
        $summary['orders_updated'] = 'FAILED';
    }

    // =========================================================================
    // SECTION 7: ORDER PAYMENT
    // =========================================================================
    echo "\n=== Section 7: ORDER PAYMENT ===\n";
    $pdo->beginTransaction();
    try {
        $payCount = 0;

        $stmt = $pdo->query("
            SELECT c.id AS cart_id, c.store_id, c.payment_method
            FROM `$sourceDb`.ecommerce_cart c
            WHERE c.action_type = 'checkout'
              AND c.store_id IN (9, 10)
        ");

        $findOrder = $pdo->prepare("
            SELECT id FROM `$tenantDb`.orders WHERE increment_id = :increment_id LIMIT 1
        ");

        $checkPayment = $pdo->prepare("
            SELECT id FROM `$tenantDb`.order_payment WHERE order_id = :order_id LIMIT 1
        ");

        $insertPayment = $pdo->prepare("
            INSERT INTO `$tenantDb`.order_payment (order_id, method, method_title, created_at, updated_at)
            VALUES (:order_id, :method, :method_title, :now, :now)
        ");

        while ($row = $stmt->fetch()) {
            $ch = $channelsByStore[$row['store_id']];
            $incrementId = $ch['inc_prefix'] . $row['cart_id'];

            $findOrder->execute([':increment_id' => $incrementId]);
            $order = $findOrder->fetch();
            if (!$order) continue;

            // Skip if payment already exists
            $checkPayment->execute([':order_id' => $order['id']]);
            if ($checkPayment->fetch()) continue;

            $pm = strtolower(trim($row['payment_method'] ?? ''));
            if (strpos($pm, 'paypal') !== false) {
                $method = 'paypal_standard';
            } elseif (strpos($pm, 'stripe') !== false) {
                $method = 'stripe';
            } elseif (strpos($pm, 'cash') !== false || strpos($pm, 'cod') !== false) {
                $method = 'cashondelivery';
            } elseif (strpos($pm, 'bank') !== false || strpos($pm, 'manual') !== false || strpos($pm, 'transfer') !== false) {
                $method = 'moneytransfer';
            } else {
                $method = 'free';
            }

            $insertPayment->execute([
                ':order_id'     => $order['id'],
                ':method'       => $method,
                ':method_title' => $row['payment_method'] ?? $method,
                ':now'          => $now,
            ]);
            $payCount++;
        }

        $pdo->commit();
        echo "Section 7: $payCount payment records inserted\n";
        $summary['payments'] = $payCount;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Section 7 FAILED: " . $e->getMessage() . "\n";
        $summary['payments'] = 'FAILED';
    }

    // =========================================================================
    // FINAL SUMMARY
    // =========================================================================
    echo "\n========================================\n";
    echo "       MIGRATION SUMMARY\n";
    echo "========================================\n";
    foreach ($summary as $key => $val) {
        echo sprintf("  %-20s %s\n", $key . ':', $val);
    }
    echo "========================================\n";
    echo "Done.\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
