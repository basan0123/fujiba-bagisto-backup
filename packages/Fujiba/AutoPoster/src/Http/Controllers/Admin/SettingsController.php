<?php

namespace Fujiba\AutoPoster\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\AutoPoster\Models\AutoPosterTemplate;
use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Fujiba\AutoPoster\Services\LiveChatLocator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $channels  = Channel::all();
        $channelId = (int) $request->input('channel_id') ?: $channels->first()?->id;

        if (! $channelId) {
            return view('autoposter::admin.settings', ['channels' => $channels, 'channelId' => null]);
        }

        $settings = AutoPosterSetting::where('channel_id', $channelId)->first()
            ?? AutoPosterSetting::defaultsFor($channelId);

        return view('autoposter::admin.settings', [
            'channels'   => $channels,
            'channelId'  => $channelId,
            'settings'   => $settings,
            'catalog'    => FacebookCatalogSetting::where('channel_id', $channelId)->first(),
            'livechat'   => app(LiveChatLocator::class)->status($channelId),
            'templates'  => AutoPosterTemplate::where('channel_id', $channelId)->orderBy('name')->get(),
            'categories' => Category::orderBy('id')->get(),
            'scheduleOn' => (bool) config('autoposter.schedule_enabled'),
        ]);
    }

    public function save(Request $request)
    {
        $channelId = (int) $request->input('channel_id');

        if (! Channel::where('id', $channelId)->exists()) {
            return back()->with('error', 'Invalid channel.');
        }

        $validated = $request->validate([
            'posts_per_day'         => ['required', 'integer', 'min:1', 'max:20'],
            'timezone'              => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'posting_window_start'  => ['required', 'date_format:H:i'],
            'posting_window_end'    => ['required', 'date_format:H:i', 'after:posting_window_start'],
            'selection_mode'        => ['required', Rule::in([
                AutoPosterSetting::MODE_RANDOM,
                AutoPosterSetting::MODE_NEW_ARRIVALS,
                AutoPosterSetting::MODE_CATEGORY,
                AutoPosterSetting::MODE_PRODUCT_SET,
                AutoPosterSetting::MODE_NEVER_POSTED,
                AutoPosterSetting::MODE_LEAST_RECENTLY_POSTED,
                AutoPosterSetting::MODE_MANUAL,
            ])],
            'products_per_post'     => ['required', 'integer', 'min:1', 'max:10'],
            'repeat_cooldown_days'  => ['required', 'integer', 'min:0', 'max:365'],
            // carousel_ad is intentionally NOT accepted: the paid ad-creative
            // path is not built, so offering it would queue posts that cannot
            // be published.
            'default_format'        => ['required', Rule::in([
                AutoPosterSetting::FORMAT_MULTI_PHOTO,
                AutoPosterSetting::FORMAT_SINGLE_IMAGE,
            ])],
            'default_language'      => ['required', 'string', 'max:20'],
            'default_template_id'   => ['nullable', 'integer'],
            'messenger_destination' => ['nullable', 'string', 'max:255', 'url'],
            'messenger_ref'         => ['nullable', 'string', 'max:100'],
            'max_retries'           => ['required', 'integer', 'min:0', 'max:10'],
            'posting_times'         => ['nullable', 'string'],
        ]);

        $settings = AutoPosterSetting::firstOrNew(['channel_id' => $channelId]);

        $settings->fill([
            'posts_per_day'         => (int) $validated['posts_per_day'],
            'timezone'              => $validated['timezone'],
            'posting_window_start'  => $validated['posting_window_start'] . ':00',
            'posting_window_end'    => $validated['posting_window_end'] . ':00',
            'selection_mode'        => $validated['selection_mode'],
            'selection_config'      => $this->selectionConfig($request, $validated['selection_mode']),
            'products_per_post'     => (int) $validated['products_per_post'],
            'repeat_cooldown_days'  => (int) $validated['repeat_cooldown_days'],
            'default_format'        => $validated['default_format'],
            'default_language'      => $validated['default_language'],
            'default_template_id'   => $validated['default_template_id'] ?: null,
            'messenger_destination' => $validated['messenger_destination'] ?: null,
            'messenger_ref'         => $validated['messenger_ref'] ?: null,
            'auto_retry_enabled'    => $request->boolean('auto_retry_enabled'),
            'max_retries'           => (int) $validated['max_retries'],
            'posting_times'         => $this->parsePostingTimes($validated['posting_times'] ?? null),
        ]);

        // auto_posting_enabled is NOT settable here — it goes through the
        // dashboard toggle so it can never be switched on without pre-flight.
        if (! $settings->exists) {
            $settings->auto_posting_enabled = false;
        }

        $settings->save();

        return back()->with('success', 'Settings saved.');
    }

    /**
     * "09:00, 12:30, 16:00" -> ['09:00','12:30','16:00'], or null to
     * auto-distribute. Invalid entries are dropped rather than saved, so a typo
     * cannot produce a slot that never fires.
     *
     * @return array<int,string>|null
     */
    protected function parsePostingTimes(?string $raw): ?array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        $times = [];
        foreach (preg_split('/[,\s]+/', $raw) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            // Accept 9:00 as well as 09:00.
            if (preg_match('/^(\d{1,2}):(\d{2})$/', $part, $m)
                && (int) $m[1] <= 23 && (int) $m[2] <= 59) {
                $times[sprintf('%02d:%02d', (int) $m[1], (int) $m[2])] = true;
            }
        }

        if (empty($times)) {
            return null;
        }

        $times = array_keys($times);
        sort($times);

        return $times;
    }

    protected function selectionConfig(Request $request, string $mode): ?array
    {
        return match ($mode) {
            AutoPosterSetting::MODE_CATEGORY => [
                'category_ids' => array_values(array_filter(array_map(
                    'intval',
                    (array) $request->input('category_ids', [])
                ))),
            ],
            AutoPosterSetting::MODE_NEW_ARRIVALS => [
                'new_arrival_days' => max(1, (int) $request->input('new_arrival_days', 30)),
            ],
            AutoPosterSetting::MODE_PRODUCT_SET => [
                'retailer_ids' => array_values(array_filter(array_map(
                    'trim',
                    preg_split('/[,\r\n]+/', (string) $request->input('retailer_ids', ''))
                ))),
            ],
            default => null,
        };
    }
}
