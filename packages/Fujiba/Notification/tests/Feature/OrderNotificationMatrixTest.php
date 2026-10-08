<?php

// packages/Fujiba/Notification/tests/Feature/OrderNotificationMatrixTest.php
//
// Customer order-status notifications: the matrix, duplicate protection and
// delivery logging, end to end through the real listener, job and
// MessengerChannel with Graph faked.
//
// SAFETY: the suite has no test database of its own (phpunit.xml sets none),
// so this file builds a scratch MySQL schema `tenant_zz_notiftest` (tables
// copied LIKE the kimono tenant), points every connection the code touches
// at it, ASSERTS that before any write, and drops it afterwards. Facebook is
// faked with Http::fake — nothing reaches a real customer.
//
// Run: vendor/bin/pest packages/Fujiba/Notification/tests/Feature/OrderNotificationMatrixTest.php

use Fujiba\Notification\Jobs\SendMessengerNotificationJob;
use Fujiba\Notification\Listeners\OrderEventListener;
use Fujiba\Notification\Models\NotificationLog;
use Fujiba\Notification\Services\OrderNotificationMatrix;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Shipment;

abstract class NotificationMatrixTestCase extends BaseTestCase {}

uses(NotificationMatrixTestCase::class);

const NOTIF_TEST_DB  = 'tenant_zz_notiftest';
const NOTIF_SRC_DB   = 'tenant_1bd1181f-fcd5-4238-a12d-a207d6ea4006';
const NOTIF_PSID     = '9990000000000001';
const NOTIF_TABLES   = [
    'notification_log', 'notification_templates', 'customer_channel_tokens',
    'notification_admin_recipients', 'pos_order', 'orders', 'order_items',
    'invoices', 'shipments', 'refunds', 'core_config', 'channels', 'customers',
];

/**
 * Stand-ins for the parts that need a full storefront (URL signing, price
 * formatting, locale lookup); every matrix / idempotency / send path is real.
 */
class MatrixTestListener extends OrderEventListener
{
    protected function buildVars(Order $order, mixed $payload): array
    {
        $tracking = $payload instanceof Shipment ? trim((string) $payload->track_number) : '';

        return [
            'order' => [
                'increment_id'    => $order->increment_id,
                'grand_total'     => '¥1,000',
                'tracking_number' => $tracking,
                'tracking_url'    => $tracking !== '' ? 'https://track.example/'.$tracking : '',
                'carrier'         => $payload instanceof Shipment ? (string) $payload->carrier_title : '',
            ],
            'customer'     => ['first_name' => 'Aye'],
            'tracking_url' => $tracking !== '' ? 'https://track.example/'.$tracking : '',
            'action_url'   => 'https://kimono.example/o/'.$order->id,
        ];
    }

    protected function resolveLanguage(Order $order): string
    {
        return 'en';
    }

    protected function hasWholesaleDiscount(Order $order): bool
    {
        return true;
    }
}

function notifDb(): \Illuminate\Database\Connection
{
    return DB::connection('mysql');
}

beforeEach(function () {
    $central = config('database.connections.mysql');

    // Build the scratch schema on a dedicated connection first.
    config(['database.connections.notif_admin' => array_merge($central, ['database' => NOTIF_SRC_DB])]);
    $admin = DB::connection('notif_admin');
    $admin->statement('DROP DATABASE IF EXISTS `'.NOTIF_TEST_DB.'`');
    $admin->statement('CREATE DATABASE `'.NOTIF_TEST_DB.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    foreach (NOTIF_TABLES as $t) {
        $admin->statement('CREATE TABLE `'.NOTIF_TEST_DB."`.`$t` LIKE `".NOTIF_SRC_DB."`.`$t`");
    }

    $admin->statement('CREATE TABLE `'.NOTIF_TEST_DB.'`.`livechat_settings` LIKE `bagisto_central`.`livechat_settings`');
    $admin->statement('CREATE TABLE `'.NOTIF_TEST_DB.'`.`facebook_rx_fb_page_info` (id INT PRIMARY KEY, page_id VARCHAR(64), page_access_token TEXT)');

    // Point every connection the code uses at the scratch DB.
    foreach (['mysql', 'chatpion_import'] as $conn) {
        config(["database.connections.$conn" => array_merge($central, ['database' => NOTIF_TEST_DB])]);
        DB::purge($conn);
    }

    expect(DB::connection()->getDatabaseName())->toBe(NOTIF_TEST_DB)
        ->and(DB::connection('chatpion_import')->getDatabaseName())->toBe(NOTIF_TEST_DB);

    // The real migration under test adds idempotency_key.
    $migration = require __DIR__.'/../../database/migrations/tenant/2026_10_09_000001_add_idempotency_key_to_notification_log.php';
    $migration->up();

    config(['queue.default' => 'sync', 'fujiba-notification.status_updates' => true]);
    app()->forgetInstance(OrderEventListener::class);
    app()->bind(OrderEventListener::class, MatrixTestListener::class);

    $db = notifDb();
    $db->table('channels')->insert(['id' => 1, 'code' => 'default', 'theme' => 'default', 'root_category_id' => 1, 'default_locale_id' => 1, 'base_currency_id' => 1]);
    $db->table('facebook_rx_fb_page_info')->insert(['id' => 1, 'page_id' => '103196179075087', 'page_access_token' => 'TEST_TOKEN']);
    $db->table('customer_channel_tokens')->insert([
        'customer_id' => 501, 'provider' => 'messenger', 'token' => NOTIF_PSID,
        'page_id' => '103196179075087', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);

    foreach (['order.placed', 'order.pending_payment', 'order.processing', 'order.invoiced', 'order.shipped', 'order.completed', 'order.cancelled', 'order.refunded'] as $event) {
        $body = match ($event) {
            'order.shipped' => "📦 Order #{{ order.increment_id }} has shipped.\nCarrier: {{ order.carrier }}\nTracking: {{ order.tracking_number }}",
            default         => strtoupper(substr($event, 6))." #{{ order.increment_id }}",
        };

        $db->table('notification_templates')->insert([
            'event' => $event, 'notification_channel' => 'messenger', 'recipient_type' => 'customer',
            'language' => 'en', 'channel_id' => null, 'body' => $body, 'is_active' => 1,
            'buttons' => json_encode([['text' => 'View Order', 'type' => 'url', 'value' => '{{action_url}}']]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // One fake for the whole test; $GLOBALS['graph_reply'] switches it.
    $GLOBALS['graph_reply'] = [['recipient_id' => NOTIF_PSID, 'message_id' => 'm_test'], 200];
    Http::fake(['graph.facebook.com/*' => fn () => Http::response(...$GLOBALS['graph_reply'])]);
});

afterEach(function () {
    DB::purge('mysql');
    DB::purge('chatpion_import');
    DB::connection('notif_admin')->statement('DROP DATABASE IF EXISTS `'.NOTIF_TEST_DB.'`');
});

function makeOrder(string $status = 'pending', array $attrs = []): Order
{
    $order = new Order;
    $order->forceFill(array_merge([
        'increment_id' => (string) random_int(10000, 99999), 'status' => $status, 'channel_id' => 1,
        'customer_id' => 501, 'customer_email' => 'aye@example.test', 'customer_first_name' => 'Aye',
        'is_guest' => 0, 'channel_name' => 'Kimono', 'order_currency_code' => 'JPY',
        'base_currency_code' => 'JPY', 'channel_currency_code' => 'JPY', 'grand_total' => 1000,
        'base_grand_total' => 1000, 'sub_total' => 1000, 'base_sub_total' => 1000,
    ], $attrs))->save();

    return $order->fresh();
}

function fire(string $event, mixed $payload): void
{
    app(OrderEventListener::class)->handle($event, $payload);
}

function flipStatus(Order $order, string $status): void
{
    $order->status = $status;
    $order->save();
    fire('sales.order.update-status.after', $order);
}

function customerLogs(Order $order, ?string $event = null)
{
    return NotificationLog::query()
        ->where('subject_id', $order->id)
        ->where('notification_channel', 'messenger')
        ->when($event, fn ($q) => $q->where('event', $event))
        ->get();
}

function graphSends(): int
{
    return count(Http::recorded(fn ($r) => str_contains($r->url(), 'graph.facebook.com')));
}

function makeShipment(Order $order, ?string $track, string $carrier = 'Sagawa'): Shipment
{
    $id = notifDb()->table('shipments')->insertGetId([
        'order_id' => $order->id, 'track_number' => $track, 'carrier_title' => $carrier,
        'total_qty' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);

    return Shipment::find($id);
}

it('1. order placed sends exactly one message (existing flow preserved, POST_PURCHASE_UPDATE + button)', function () {
    $order = makeOrder('pending');
    fire('checkout.order.save.after', $order);

    expect(customerLogs($order, 'order.placed'))->toHaveCount(1)
        ->and(graphSends())->toBe(1);

    $req = Http::recorded()[0][0];
    expect($req['messaging_type'])->toBe('MESSAGE_TAG')
        ->and($req['tag'])->toBe('POST_PURCHASE_UPDATE')
        ->and($req['message']['attachment']['payload']['buttons'][0]['title'])->toBe('View Order');
});

it('2. processing status sends one message', function () {
    $order = makeOrder('pending');
    flipStatus($order, 'processing');

    expect(customerLogs($order, 'order.processing'))->toHaveCount(1)->and(graphSends())->toBe(1);
});

it('2b. pending payment sends one message only when no placed message went out', function () {
    $order = makeOrder('processing');
    flipStatus($order, 'pending_payment');
    expect(customerLogs($order, 'order.pending_payment'))->toHaveCount(1);

    $placed = makeOrder('pending');
    fire('checkout.order.save.after', $placed);
    flipStatus($placed, 'pending_payment');
    expect(customerLogs($placed, 'order.pending_payment'))->toHaveCount(0);
});

it('3. invoice creation sends one invoiced message and suppresses the side-effect processing message', function () {
    $order = makeOrder('pending');
    $id = notifDb()->table('invoices')->insertGetId([
        'order_id' => $order->id, 'state' => 'paid', 'total_qty' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);

    // Core order: updateOrderStatus (processing) THEN invoice.save.after.
    flipStatus($order, 'processing');
    fire('sales.invoice.save.after', Invoice::find($id));

    expect(customerLogs($order, 'order.invoiced'))->toHaveCount(1)
        ->and(customerLogs($order, 'order.processing'))->toHaveCount(0)
        ->and(graphSends())->toBe(1);
});

it('4 + 8. shipment sends one shipped message even when status + shipment events both fire', function () {
    $order    = makeOrder('pending');
    $shipment = makeShipment($order, 'SG123');

    flipStatus($order, 'processing');           // side effect of shipment creation
    fire('sales.shipment.save.after', $shipment);
    fire('sales.shipment.save.after', $shipment); // duplicate event

    expect(customerLogs($order, 'order.shipped'))->toHaveCount(1)
        ->and(customerLogs($order, 'order.processing'))->toHaveCount(0)
        ->and(graphSends())->toBe(1);

    $msg = Http::recorded()[0][0]['message']['attachment']['payload'];
    expect($msg['text'])->toContain('Carrier: Sagawa')->toContain('Tracking: SG123')
        ->and(collect($msg['buttons'])->pluck('url'))->toContain('https://track.example/SG123');
});

it('9. missing tracking number is never invented', function () {
    $order = makeOrder('processing');
    fire('sales.shipment.save.after', makeShipment($order, null, ''));

    $msg = Http::recorded()[0][0]['message']['attachment']['payload'];
    expect($msg['text'])->not->toContain('Tracking')->not->toContain('Carrier')
        ->and($msg['buttons'])->toHaveCount(1)
        ->and($msg['buttons'][0]['title'])->toBe('View Order');
});

it('5. completed status sends one message', function () {
    $order = makeOrder('processing');
    flipStatus($order, 'completed');

    expect(customerLogs($order, 'order.completed'))->toHaveCount(1)->and(graphSends())->toBe(1);
});

it('6. cancel sends one message although cancel.after AND update-status.after both fire', function () {
    $order = makeOrder('pending');
    $order->status = 'canceled';
    $order->save();

    fire('sales.order.cancel.after', $order);
    fire('sales.order.update-status.after', $order);

    expect(customerLogs($order, 'order.cancelled'))->toHaveCount(1)->and(graphSends())->toBe(1);
});

it('7. duplicate events send nothing extra', function () {
    $order = makeOrder('processing');
    flipStatus($order, 'completed');
    flipStatus($order, 'completed');
    fire('sales.order.update-status.after', $order);

    expect(customerLogs($order))->toHaveCount(1)->and(graphSends())->toBe(1);
});

it('7b. legacy (pre-key) completed log still blocks a re-send', function () {
    $order = makeOrder('completed');
    NotificationLog::create([
        'event' => 'order.completed', 'notification_channel' => 'messenger', 'subject_type' => Order::class,
        'subject_id' => $order->id, 'customer_id' => 501, 'recipient' => NOTIF_PSID, 'status' => 'sent',
    ]);

    fire('sales.order.update-status.after', $order);

    expect(graphSends())->toBe(0);
});

it('status changes that mean nothing to the customer send nothing', function () {
    foreach (['pending', 'closed', 'fraud'] as $status) {
        flipStatus(makeOrder('processing'), $status);
    }

    expect(graphSends())->toBe(0);
});

it('POS counter orders get no status messages, but staff updating a WEB order from the POS still notify', function () {
    $pos = makeOrder('processing');
    notifDb()->table('pos_order')->insert(['order_id' => $pos->id, 'order_ref_id' => 'X']);
    flipStatus($pos, 'completed');
    expect(customerLogs($pos))->toHaveCount(0);

    $web = makeOrder('processing');
    flipStatus($web, 'completed');
    expect(customerLogs($web))->toHaveCount(1);
});

it('10. a Facebook failure is logged as failed and the same row is retried, not duplicated', function () {
    $GLOBALS['graph_reply'] = [['error' => ['message' => '(#10) outside window']], 400];

    $order = makeOrder('processing');
    flipStatus($order, 'completed');

    $log = customerLogs($order)->sole();
    expect($log->status)->toBe('failed')->and($log->error)->toContain('outside window');

    $GLOBALS['graph_reply'] = [['message_id' => 'm_retry'], 200];
    (new SendMessengerNotificationJob($log->id, '', 'COMPLETED', ['channel_id' => 1, 'page_id' => '103196179075087']))
        ->handle(app(\Fujiba\Notification\Services\ChannelManager::class));

    $log->refresh();
    expect($log->status)->toBe('sent')
        ->and($log->payload['facebook_message_id'])->toBe('m_retry')
        ->and(customerLogs($order))->toHaveCount(1);
});

it('9b. every attempt records trigger, template, tag, key and the Facebook message id', function () {
    $order = makeOrder('processing');
    flipStatus($order, 'completed');

    $log = customerLogs($order)->sole();
    expect($log->idempotency_key)->toStartWith("order:{$order->id}:completed:once:messenger:")
        ->and($log->payload)->toMatchArray([
            'trigger'             => 'sales.order.update-status.after',
            'trigger_kind'        => 'status',
            'message_tag'         => 'POST_PURCHASE_UPDATE',
            'facebook_message_id' => 'm_test',
        ])
        ->and($log->payload['template_id'])->toBeInt();
});

it('11. historical completed orders are never notified automatically (audit backfill is dry-run)', function () {
    makeOrder('completed');

    Artisan::call('fujiba:orders:notification-audit', ['--backfill' => true, '--days' => 30]);

    expect(Artisan::output())->toContain('DRY-RUN')
        ->and(graphSends())->toBe(0)
        ->and(NotificationLog::count())->toBe(0);
});

it('13. the dead bagisto.shop.* events are not relied on; the real core event is wired', function () {
    expect(Event::hasListeners('bagisto.shop.order.update-status.after'))->toBeFalse()
        ->and(Event::hasListeners('bagisto.shop.checkout.order.after'))->toBeFalse()
        ->and(array_keys(OrderNotificationMatrix::EVENTS))->toContain('sales.order.update-status.after')
        ->and(file_exists(base_path('packages/Fujiba/LiveChat/src/Listeners/OrderStatusNotifier.php')))->toBeFalse();

    // The closure the Notification provider registered on the REAL core
    // event (other packages' listeners on it — stock, ranks — are left out).
    $ours = collect(Event::getRawListeners()['sales.order.update-status.after'] ?? [])
        ->filter(fn ($l) => $l instanceof Closure
            && str_contains((new ReflectionFunction($l))->getFileName(), 'Notification/src/Providers'));
    expect($ours)->toHaveCount(1);

    $order = makeOrder('processing');
    $order->status = 'completed';
    $order->save();
    ($ours->first())($order);

    expect(customerLogs($order, 'order.completed'))->toHaveCount(1);
});

it('14. order updates never use RCN/OTN tokens — Kimono Notify stays separate', function () {
    $order = makeOrder('processing');
    flipStatus($order, 'completed');

    $req = Http::recorded()[0][0];
    expect($req['recipient'])->toBe(['id' => NOTIF_PSID])
        ->and($req['tag'])->toBe('POST_PURCHASE_UPDATE');

    $src = collect(glob(__DIR__.'/../../src/{*,*/*,*/*/*}.php', GLOB_BRACE))
        ->map(fn ($f) => file_get_contents($f))->implode("\n");
    expect($src)->not->toContain('notification_messages_token')
        ->not->toContain('one_time_notif_token');
});
