<?php

namespace Fujiba\Notification\Http\Controllers\Admin;

use Fujiba\Notification\Models\NotificationAdminRecipient;
use Fujiba\Notification\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;

class NotificationTemplateController extends Controller
{
    public const EVENT_LABELS = [
        'order.placed'    => '注文受付 / Order Placed',
        'order.invoiced'  => '請求書 / Invoiced',
        'order.shipped'   => '発送 / Shipped',
        'order.cancelled' => 'キャンセル / Cancelled',
        'order.refunded'  => '返金 / Refunded',
    ];

    public const ACTIVE_CHANNEL_LABELS = [
        'mail'      => 'メール / Email',
        'messenger' => 'Messenger',
    ];

    public const COMING_SOON_CHANNEL_LABELS = [
        'line'      => 'LINE',
        'whatsapp'  => 'WhatsApp',
        'telegram'  => 'Telegram',
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
    ];

    public const VARIABLES = [
        'customer_name',
        'order_id',
        'order_status',
        'order_total',
        'order_date',
        'store_name',
        'action_url',
        'tracking_number',
        'tracking_url',
        'product_list',
    ];

    public function index(Request $request)
    {
        // Admin Alerts tab — list/manage admin recipients per channel.
        if ($request->get('view') === 'recipients') {
            return $this->recipientsIndex($request);
        }

        // Recipient type for templates view: customer (default) or admin.
        $recipientType = $request->get('recipient_type', NotificationTemplate::RECIPIENT_CUSTOMER);
        if (! in_array($recipientType, [NotificationTemplate::RECIPIENT_CUSTOMER, NotificationTemplate::RECIPIENT_ADMIN], true)) {
            $recipientType = NotificationTemplate::RECIPIENT_CUSTOMER;
        }

        $event    = $request->get('event', 'order.placed');
        $channel  = $request->get('channel', 'mail');

        if (! array_key_exists($event, self::EVENT_LABELS)) {
            $event = 'order.placed';
        }
        if (! array_key_exists($channel, self::ACTIVE_CHANNEL_LABELS)
            && ! array_key_exists($channel, self::COMING_SOON_CHANNEL_LABELS)) {
            $channel = 'mail';
        }

        // Bagisto channels for this tenant. The "Default (all)" pseudo-option
        // writes channel_id = NULL — runtime falls back to it when no
        // channel-specific row exists.
        $channels = Channel::with('locales')->orderBy('id')->get();

        // channel_id query param: null means "Default (all)", otherwise an int
        $channelIdParam = $request->get('channel_id');
        $selectedChannelId = ($channelIdParam === null || $channelIdParam === '' || $channelIdParam === 'default')
            ? null
            : (int) $channelIdParam;

        // Validate selected channel id is real
        if ($selectedChannelId !== null && ! $channels->contains('id', $selectedChannelId)) {
            $selectedChannelId = null;
        }

        $selectedChannel = $selectedChannelId
            ? $channels->firstWhere('id', $selectedChannelId)
            : null;

        // Languages from channel locales (per-channel) or union across all
        // channels (when "Default" is selected — admin needs to author for
        // any locale that any channel supports).
        $languages = $selectedChannel
            ? $selectedChannel->locales->pluck('code')->map(fn ($c) => strtolower(substr($c, 0, 2)))->unique()->values()->toArray()
            : $channels->flatMap->locales->pluck('code')->map(fn ($c) => strtolower(substr($c, 0, 2)))->unique()->values()->toArray();

        if (empty($languages)) {
            $languages = ['en']; // safety net
        }

        $language = $request->get('language', $languages[0] ?? 'en');
        if (! in_array($language, $languages, true)) {
            $language = $languages[0] ?? 'en';
        }

        // Resolve template — exact match (event, channel, recipient_type, language, channel_id)
        $template = NotificationTemplate::query()
            ->where('event', $event)
            ->where('notification_channel', $channel)
            ->where('recipient_type', $recipientType)
            ->where('language', $language)
            ->when(
                $selectedChannelId !== null,
                fn ($q) => $q->where('channel_id', $selectedChannelId),
                fn ($q) => $q->whereNull('channel_id'),
            )
            ->first();

        $existingLanguages = NotificationTemplate::query()
            ->where('event', $event)
            ->where('notification_channel', $channel)
            ->where('recipient_type', $recipientType)
            ->when(
                $selectedChannelId !== null,
                fn ($q) => $q->where('channel_id', $selectedChannelId),
                fn ($q) => $q->whereNull('channel_id'),
            )
            ->pluck('language')
            ->toArray();

        return view('fujiba_notification::admin.notification-templates.index', [
            'eventLabels'              => self::EVENT_LABELS,
            'activeChannelLabels'      => self::ACTIVE_CHANNEL_LABELS,
            'comingSoonChannelLabels'  => self::COMING_SOON_CHANNEL_LABELS,
            'languages'                => $languages,
            'variables'                => self::VARIABLES,
            'currentEvent'             => $event,
            'currentChannel'           => $channel,
            'currentLanguage'          => $language,
            'currentRecipientType'     => $recipientType,
            'channels'                 => $channels,
            'selectedChannelId'        => $selectedChannelId,
            'template'                 => $template,
            'existingLanguages'        => $existingLanguages,
        ]);
    }

    /**
     * Admin Alerts list view — recipients per channel with add/delete.
     */
    protected function recipientsIndex(Request $request)
    {
        $channels = Channel::orderBy('id')->get();
        $recipients = NotificationAdminRecipient::query()->orderBy('channel_id')->orderBy('id')->get();

        return view('fujiba_notification::admin.notification-templates.recipients', [
            'channels'   => $channels,
            'recipients' => $recipients,
        ]);
    }

    public function recipientsStore(Request $request)
    {
        $channelIds = Channel::pluck('id')->toArray();

        $data = $request->validate([
            'channel_id' => 'nullable|integer|in:'.(empty($channelIds) ? '0' : implode(',', $channelIds)),
            'page_id'    => 'required|string|max:100',
            'psid'       => 'required|string|max:64',
            'label'      => 'nullable|string|max:100',
            'is_active'  => 'nullable|boolean',
        ]);

        $channelId = ($data['channel_id'] ?? '') === '' ? null : (int) $data['channel_id'];

        NotificationAdminRecipient::firstOrCreate(
            [
                'channel_id' => $channelId,
                'page_id'    => $data['page_id'],
                'psid'       => $data['psid'],
            ],
            [
                'label'     => $data['label']     ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ],
        );

        return redirect()->route('admin.fujiba.notifications.index', ['view' => 'recipients'])
            ->with('success', 'Admin recipient added.');
    }

    public function recipientsToggle(int $id)
    {
        $r = NotificationAdminRecipient::findOrFail($id);
        $r->update(['is_active' => ! $r->is_active]);
        return back()->with('success', 'Status updated.');
    }

    public function recipientsDestroy(int $id)
    {
        NotificationAdminRecipient::findOrFail($id)->delete();
        return redirect()->route('admin.fujiba.notifications.index', ['view' => 'recipients'])
            ->with('success', 'Admin recipient removed.');
    }

    public function save(Request $request)
    {
        $channelIds = Channel::pluck('id')->toArray();

        $data = $request->validate([
            'event'                => 'required|string|in:'.implode(',', array_keys(self::EVENT_LABELS)),
            'notification_channel' => 'required|string|in:'.implode(',', array_keys(self::ACTIVE_CHANNEL_LABELS)),
            'recipient_type'       => 'nullable|string|in:customer,admin',
            'language'             => 'required|string|max:10',
            'channel_id'           => 'nullable|integer|in:'.(empty($channelIds) ? '0' : implode(',', $channelIds)),
            'is_active'            => 'nullable|boolean',
            'subject'              => 'nullable|string|max:255',
            'from_name'            => 'nullable|string|max:100',
            'from_email'           => 'nullable|email|max:100',
            'body'                 => 'required|string',
            'buttons'              => 'nullable|array',
            'buttons.*.text'       => 'nullable|string|max:60',
            'buttons.*.type'       => 'nullable|string|in:url,postback,phone',
            'buttons.*.value'      => 'nullable|string|max:500',
        ]);

        $channelId = $data['channel_id'] ?? null;
        $channelId = ($channelId === '' || $channelId === null) ? null : (int) $channelId;
        $recipientType = $data['recipient_type'] ?? 'customer';

        $payload = [
            'subject'    => $data['subject']    ?? null,
            'from_name'  => $data['from_name']  ?? null,
            'from_email' => $data['from_email'] ?? null,
            'body'       => $data['body'],
            'buttons'    => $this->normalizeButtons($data['buttons'] ?? []),
            'is_active'  => (bool) ($data['is_active'] ?? false),
        ];

        if ($data['notification_channel'] !== 'mail') {
            $payload['subject']    = null;
            $payload['from_name']  = null;
            $payload['from_email'] = null;
        }

        if ($data['notification_channel'] === 'mail') {
            // Mail doesn't use buttons today.
            $payload['buttons'] = null;
        }

        // Cannot use updateOrCreate here because it can't disambiguate
        // channel_id IS NULL via array key match. Hand-rolled lookup:
        $existing = NotificationTemplate::query()
            ->where('event', $data['event'])
            ->where('notification_channel', $data['notification_channel'])
            ->where('recipient_type', $recipientType)
            ->where('language', $data['language'])
            ->when(
                $channelId !== null,
                fn ($q) => $q->where('channel_id', $channelId),
                fn ($q) => $q->whereNull('channel_id'),
            )
            ->first();

        if ($existing) {
            $existing->update($payload);
        } else {
            NotificationTemplate::create($payload + [
                'event'                => $data['event'],
                'notification_channel' => $data['notification_channel'],
                'recipient_type'       => $recipientType,
                'language'             => $data['language'],
                'channel_id'           => $channelId,
            ]);
        }

        return redirect()->route('admin.fujiba.notifications.index', [
            'event'          => $data['event'],
            'channel'        => $data['notification_channel'],
            'recipient_type' => $recipientType,
            'language'       => $data['language'],
            'channel_id'     => $channelId,
        ])->with('success', 'Template saved.');
    }

    public function toggle(Request $request, int $id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('success', 'Status updated.');
    }

    public function destroy(int $id)
    {
        $template  = NotificationTemplate::findOrFail($id);
        $event     = $template->event;
        $channel   = $template->notification_channel;
        $language  = $template->language;
        $channelId = $template->channel_id;
        $template->delete();

        return redirect()->route('admin.fujiba.notifications.index', [
            'event'      => $event,
            'channel'    => $channel,
            'language'   => $language,
            'channel_id' => $channelId,
        ])->with('success', 'Template deleted.');
    }

    /**
     * Strip any partially-filled rows (text/value/type all required for a
     * usable button). Empty array stored as NULL.
     */
    protected function normalizeButtons(array $buttons): ?array
    {
        $clean = [];
        foreach ($buttons as $b) {
            $text  = trim((string) ($b['text']  ?? ''));
            $type  = trim((string) ($b['type']  ?? ''));
            $value = trim((string) ($b['value'] ?? ''));
            if ($text === '' || $type === '' || $value === '') {
                continue;
            }
            $clean[] = ['text' => $text, 'type' => $type, 'value' => $value];
        }

        return empty($clean) ? null : $clean;
    }
}
