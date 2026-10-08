<?php

namespace Fujiba\Notification\Console\Commands;

use Fujiba\Notification\Models\NotificationTemplate;
use Illuminate\Console\Command;

/**
 * Brings a tenant's customer Messenger templates in line with the
 * order-notification matrix. Dry-run unless --apply. Run per tenant:
 *
 *   php artisan tenants:run fujiba:notifications:install-status-templates --option="apply=1"
 *
 * 1. Adds order.processing / order.pending_payment templates in every
 *    language the tenant already uses — only on tenants that already run
 *    lifecycle (cancelled) messages, so minimal stores stay minimal.
 * 2. Repairs rows still holding the "✅ Order … confirmed." order-placed text
 *    copied onto other events (cancelled/shipped customers were being told
 *    their order was confirmed).
 * 3. Upgrades the untouched stock shipped text to include the carrier line.
 *
 * Rows an admin has customised are never touched.
 */
class InstallStatusTemplates extends Command
{
    protected $signature = 'fujiba:notifications:install-status-templates {--apply : Write changes (default is dry-run)}';

    protected $description = 'Install/repair Messenger order-status templates for the notification matrix (dry-run by default)';

    protected const CONFIRMED_MARKER = '✅ Order #{{ order.increment_id }} confirmed.';

    /** Old stock shipped bodies that may be upgraded in place. */
    protected const OLD_SHIPPED = [
        "📦 Order #{{ order.increment_id }} shipped.\nTracking: {{ order.tracking_number }}",
        "📦 ご注文（{{ order.increment_id }}）を発送しました。\n追跡番号: {{ order.tracking_number }}",
        "📦 Order #{{ order.increment_id }} ပို့ဆောင်ပြီးပါပြီ။\nTracking: {{ order.tracking_number }}\nကျေးဇူးတင်ပါတယ် {{ customer.first_name }} 🙏",
    ];

    protected const BUTTON_LABEL = ['en' => 'View Order', 'ja' => '注文を確認', 'my' => 'Order ကြည့်ရန်'];

    public static function bodies(): array
    {
        return [
            'order.processing' => [
                'en' => "🧵 Order #{{ order.increment_id }} is now being processed.\nWe're preparing your items and will message you again when it ships.\nThank you, {{ customer.first_name }} 🙏",
                'ja' => "🧵 ご注文（{{ order.increment_id }}）の準備を開始しました。\n発送の際にあらためてご連絡いたします。\n{{ customer.first_name }} 様、ありがとうございます 🙏",
                'my' => "🧵 Order #{{ order.increment_id }} ကို ပြင်ဆင်နေပါပြီ။\nပို့ဆောင်တဲ့အခါ ထပ်ပြီး အကြောင်းကြားပေးပါမယ်။\nကျေးဇူးတင်ပါတယ် {{ customer.first_name }} 🙏",
            ],
            'order.pending_payment' => [
                'en' => "💳 Order #{{ order.increment_id }} is waiting for payment.\nTotal: {{ order.grand_total }}\nWe'll start preparing it as soon as payment is confirmed.",
                'ja' => "💳 ご注文（{{ order.increment_id }}）はお支払い待ちです。\nお支払い金額: {{ order.grand_total }}\nお支払いを確認次第、準備を開始いたします。",
                'my' => "💳 Order #{{ order.increment_id }} ငွေပေးချေမှု စောင့်နေပါတယ်။\nစုစုပေါင်း: {{ order.grand_total }}\nငွေပေးချေပြီးတာနဲ့ ပြင်ဆင်ပေးပါမယ်။",
            ],
            'order.cancelled' => [
                'en' => "❌ Order #{{ order.increment_id }} has been cancelled.\nIf you didn't expect this or have a question, just reply to this message 🙏",
                'ja' => "❌ ご注文（{{ order.increment_id }}）はキャンセルとなりました。\nご不明な点がございましたら、このメッセージにご返信ください 🙏",
                'my' => "❌ Order #{{ order.increment_id }} ကို ပယ်ဖျက်လိုက်ပါပြီ။\nမေးစရာရှိရင် ဒီ message ကို ပြန်ပို့ပေးပါ 🙏",
            ],
            'order.shipped' => [
                'en' => "📦 Order #{{ order.increment_id }} has shipped.\nCarrier: {{ order.carrier }}\nTracking: {{ order.tracking_number }}",
                'ja' => "📦 ご注文（{{ order.increment_id }}）を発送しました。\n配送業者: {{ order.carrier }}\n追跡番号: {{ order.tracking_number }}",
                'my' => "📦 Order #{{ order.increment_id }} ပို့ဆောင်ပြီးပါပြီ။\nCarrier: {{ order.carrier }}\nTracking: {{ order.tracking_number }}\nကျေးဇူးတင်ပါတယ် {{ customer.first_name }} 🙏",
            ],
        ];
    }

    public function handle(): int
    {
        $apply  = (bool) $this->option('apply');
        $bodies = self::bodies();
        $base   = NotificationTemplate::query()
            ->where('notification_channel', 'messenger')
            ->where('recipient_type', NotificationTemplate::RECIPIENT_CUSTOMER);

        $this->line(($apply ? 'APPLY' : 'DRY-RUN').' tenant '.(function_exists('tenant') && tenant() ? tenant('id') : 'central'));

        if (! \Illuminate\Support\Facades\Schema::hasTable('notification_templates')) {
            $this->line('  notification package not installed on this tenant — skipped');

            return self::SUCCESS;
        }

        // 1 + 2: repair copy-pasted "confirmed" bodies on non-placed events.
        foreach ((clone $base)->where('event', '!=', 'order.placed')->get() as $row) {
            $fixed = $bodies[$row->event][$row->language] ?? null;

            if ($fixed && str_contains((string) $row->body, self::CONFIRMED_MARKER)) {
                $this->line("  repair #{$row->id} {$row->event}/{$row->language} (said 'Order confirmed')");
                $apply && $row->update(['body' => $fixed]);
            } elseif ($row->event === 'order.shipped' && $fixed && in_array($row->body, self::OLD_SHIPPED, true)) {
                $this->line("  upgrade #{$row->id} order.shipped/{$row->language} (adds carrier line)");
                $apply && $row->update(['body' => $fixed]);
            }
        }

        // 3: add the new status types where lifecycle messages are in use.
        if (! (clone $base)->where('event', 'order.cancelled')->exists()) {
            $this->line('  no lifecycle templates on this tenant — new status types not added');

            return self::SUCCESS;
        }

        $languages = (clone $base)->whereNull('channel_id')->distinct()->pluck('language');

        foreach (['order.processing', 'order.pending_payment'] as $event) {
            foreach ($languages as $lang) {
                if (! isset($bodies[$event][$lang])) {
                    continue;
                }

                if ((clone $base)->where('event', $event)->where('language', $lang)->whereNull('channel_id')->exists()) {
                    continue;
                }

                $this->line("  add {$event}/{$lang}");

                $apply && NotificationTemplate::create([
                    'event'                => $event,
                    'notification_channel' => 'messenger',
                    'recipient_type'       => NotificationTemplate::RECIPIENT_CUSTOMER,
                    'language'             => $lang,
                    'channel_id'           => null,
                    'body'                 => $bodies[$event][$lang],
                    'buttons'              => [['text' => self::BUTTON_LABEL[$lang] ?? 'View Order', 'type' => 'url', 'value' => '{{action_url}}']],
                    'is_active'            => true,
                ]);
            }
        }

        return self::SUCCESS;
    }
}
