<?php

namespace Fujiba\Notification\Database\Seeders;

use Fujiba\Notification\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Default "View Order" button (URL points to {{action_url}} which the
        // listener generates as a per-channel signed auto-login link).
        $viewOrderEn = [['text' => 'View Order',  'type' => 'url', 'value' => '{{action_url}}']];
        $viewOrderJa = [['text' => '注文を確認',   'type' => 'url', 'value' => '{{action_url}}']];

        $templates = [
            // ---------- ORDER PLACED — MAIL ----------
            [
                'event'                => 'order.placed',
                'notification_channel' => 'mail',
                'language'             => 'en',
                'channel_id'           => null,
                'subject'              => 'Order #{{ order.increment_id }} confirmed',
                'from_name'            => '{{ store.name }}',
                'from_email'           => null,
                'body'                 => "Hi {{ customer.first_name }},\n\nWe received your order #{{ order.increment_id }} for {{ order.grand_total }} {{ order.currency }}.\n\nItems: {{ order.product_list }}\n\nView your order: {{ order.action_url }}\n\nThank you for shopping with us.\n{{ store.name }}",
                'buttons'              => null,
            ],
            [
                'event'                => 'order.placed',
                'notification_channel' => 'mail',
                'language'             => 'ja',
                'channel_id'           => null,
                'subject'              => '【{{ store.name }}】ご注文を受け付けました（注文番号: {{ order.increment_id }}）',
                'from_name'            => '{{ store.name }}',
                'from_email'           => null,
                'body'                 => "{{ customer.full_name }} 様\n\nこの度は{{ store.name }}をご利用いただきありがとうございます。\n\nご注文番号: {{ order.increment_id }}\nご注文合計: {{ order.grand_total }} {{ order.currency }}\nご注文商品: {{ order.product_list }}\n\nご注文の詳細はこちらからご確認いただけます:\n{{ order.action_url }}\n\nまたのご利用をお待ちしております。\n{{ store.name }}",
                'buttons'              => null,
            ],

            // ---------- ORDER PLACED — MESSENGER (with View Order button) ----------
            [
                'event'                => 'order.placed',
                'notification_channel' => 'messenger',
                'language'             => 'en',
                'channel_id'           => null,
                'subject'              => null,
                'from_name'            => null,
                'from_email'           => null,
                'body'                 => "✅ Order #{{ order.increment_id }} confirmed.\nTotal: {{ order.grand_total }} {{ order.currency }}\nThanks {{ customer.first_name }}!",
                'buttons'              => $viewOrderEn,
            ],
            [
                'event'                => 'order.placed',
                'notification_channel' => 'messenger',
                'language'             => 'ja',
                'channel_id'           => null,
                'subject'              => null,
                'from_name'            => null,
                'from_email'           => null,
                'body'                 => "{{ customer.first_name }}さん、ご注文ありがとうございます！\n注文番号: {{ order.increment_id }}\nご注文合計: {{ order.grand_total }} {{ order.currency }}",
                'buttons'              => $viewOrderJa,
            ],

            // ---------- ORDER SHIPPED — MAIL ----------
            [
                'event'                => 'order.shipped',
                'notification_channel' => 'mail',
                'language'             => 'en',
                'channel_id'           => null,
                'subject'              => 'Your order #{{ order.increment_id }} has shipped',
                'from_name'            => '{{ store.name }}',
                'from_email'           => null,
                'body'                 => "Hi {{ customer.first_name }},\n\nYour order #{{ order.increment_id }} has shipped.\n\nTracking number: {{ order.tracking_number }}\nTrack your package: {{ order.tracking_url }}\n\nThanks,\n{{ store.name }}",
                'buttons'              => null,
            ],
            [
                'event'                => 'order.shipped',
                'notification_channel' => 'mail',
                'language'             => 'ja',
                'channel_id'           => null,
                'subject'              => '【{{ store.name }}】ご注文（{{ order.increment_id }}）が発送されました',
                'from_name'            => '{{ store.name }}',
                'from_email'           => null,
                'body'                 => "{{ customer.full_name }} 様\n\nご注文（{{ order.increment_id }}）を発送いたしました。\n\n追跡番号: {{ order.tracking_number }}\n追跡URL: {{ order.tracking_url }}\n\n到着まで今しばらくお待ちください。\n{{ store.name }}",
                'buttons'              => null,
            ],

            // ---------- ORDER PLACED — ADMIN ALERT — MESSENGER ----------
            [
                'event'                => 'order.placed',
                'notification_channel' => 'messenger',
                'recipient_type'       => 'admin',
                'language'             => 'en',
                'channel_id'           => null,
                'subject'              => null,
                'from_name'            => null,
                'from_email'           => null,
                'body'                 => "🔔 New order #{{order_id}} — {{customer_name}} — {{order_total}}",
                'buttons'              => null,
            ],

            // ---------- ORDER SHIPPED — MESSENGER (with View Order button) ----------
            [
                'event'                => 'order.shipped',
                'notification_channel' => 'messenger',
                'language'             => 'en',
                'channel_id'           => null,
                'subject'              => null,
                'from_name'            => null,
                'from_email'           => null,
                'body'                 => "📦 Order #{{ order.increment_id }} shipped.\nTracking: {{ order.tracking_number }}",
                'buttons'              => $viewOrderEn,
            ],
            [
                'event'                => 'order.shipped',
                'notification_channel' => 'messenger',
                'language'             => 'ja',
                'channel_id'           => null,
                'subject'              => null,
                'from_name'            => null,
                'from_email'           => null,
                'body'                 => "📦 ご注文（{{ order.increment_id }}）を発送しました。\n追跡番号: {{ order.tracking_number }}",
                'buttons'              => $viewOrderJa,
            ],
        ];

        foreach ($templates as $row) {
            // firstOrCreate keyed on (event, channel, recipient_type, lang,
            // channel_id=null). Eloquent emits "channel_id IS NULL" for the
            // null branch, so existing rows are matched and skipped on re-run.
            NotificationTemplate::firstOrCreate(
                [
                    'event'                => $row['event'],
                    'notification_channel' => $row['notification_channel'],
                    'recipient_type'       => $row['recipient_type'] ?? 'customer',
                    'language'             => $row['language'],
                    'channel_id'           => null,
                ],
                $row + ['is_active' => true, 'recipient_type' => 'customer'],
            );
        }
    }
}
