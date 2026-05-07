<?php

namespace Fujiba\Notification\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $table = 'notification_templates';

    public const RECIPIENT_CUSTOMER = 'customer';
    public const RECIPIENT_ADMIN    = 'admin';

    protected $fillable = [
        'event',
        'notification_channel',
        'recipient_type',
        'language',
        'channel_id',
        'subject',
        'from_name',
        'from_email',
        'body',
        'buttons',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'buttons'   => 'array',
    ];

    /**
     * Resolution order:
     *   1. channel-specific row matching the order's channel + language
     *   2. channel-specific row matching the order's channel + English fallback
     *   3. tenant-default row (channel_id IS NULL) + language
     *   4. tenant-default row (channel_id IS NULL) + English fallback
     *
     * The first hit wins. Channel-specific always overrides tenant default.
     */
    public static function resolve(
        string $event,
        string $channel,
        string $language = 'en',
        ?int $channelId = null,
        string $recipientType = self::RECIPIENT_CUSTOMER
    ): ?self {
        $base = static::query()
            ->where('event', $event)
            ->where('notification_channel', $channel)
            ->where('recipient_type', $recipientType)
            ->where('is_active', true)
            ->where(function ($q) use ($language) {
                $q->where('language', $language)->orWhere('language', 'en');
            })
            ->orderByRaw("CASE WHEN language = ? THEN 0 ELSE 1 END", [$language]);

        if ($channelId !== null) {
            $hit = (clone $base)->where('channel_id', $channelId)->first();
            if ($hit) {
                return $hit;
            }
        }

        return (clone $base)->whereNull('channel_id')->first();
    }

    /**
     * Replace {{dot.notation}} placeholders across subject, from_*, body, and
     * each button's text/value in one pass. Same regex as TemplateRenderer
     * service — kept in sync so callers can use either entry point.
     *
     * Returns an associative array with keys subject/from_name/from_email/body/buttons.
     */
    public function render(array $vars): array
    {
        $rendered = [
            'subject'    => $this->renderString($this->subject, $vars),
            'from_name'  => $this->renderString($this->from_name, $vars),
            'from_email' => $this->renderString($this->from_email, $vars),
            'body'       => $this->renderString((string) $this->body, $vars),
            'buttons'    => null,
        ];

        if (is_array($this->buttons) && ! empty($this->buttons)) {
            $rendered['buttons'] = array_map(function (array $btn) use ($vars) {
                return [
                    'text'  => $this->renderString((string) ($btn['text']  ?? ''), $vars),
                    'type'  => (string) ($btn['type'] ?? 'url'),
                    'value' => $this->renderString((string) ($btn['value'] ?? ''), $vars),
                ];
            }, $this->buttons);
        }

        return $rendered;
    }

    protected function renderString(?string $template, array $vars): string
    {
        if ($template === null || $template === '') {
            return '';
        }

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function ($m) use ($vars) {
                $value = data_get($vars, $m[1]);

                if (is_scalar($value) || $value === null) {
                    return (string) $value;
                }

                return '';
            },
            $template
        );
    }
}
