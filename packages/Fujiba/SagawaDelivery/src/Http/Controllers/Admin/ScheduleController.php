<?php

namespace Fujiba\SagawaDelivery\Http\Controllers\Admin;

use Fujiba\SagawaDelivery\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ScheduleController extends Controller
{
    public function index()
    {
        $tenantId = $this->currentTenantId();

        $channels = core()->getAllChannels();

        $settings = Setting::query()
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('channel_id');

        return view('sagawa::admin.schedule.index', compact('channels', 'settings', 'tenantId'));
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'channel_id'       => 'required|integer',
            'processing_days'  => 'nullable|integer|min:0|max:7',
            'cutoff_hour'      => 'nullable|integer|min:0|max:23',
            'cutoff_minute'    => 'nullable|integer|min:0|max:59',
            'weekly_default'   => 'nullable|array',
            'weekly_default.*' => 'integer|min:0|max:6',
            'blackout_dates'   => 'nullable|string',
            'extra_prep_dates' => 'nullable|string',
        ]);

        $tenantId = $this->currentTenantId();

        $channel = core()->getAllChannels()->firstWhere('id', (int) $data['channel_id']);

        if (! $channel) {
            return back()->with('error', __('sagawa::app.schedule.channel-not-found'));
        }

        $existing = Setting::where('tenant_id', $tenantId)
            ->where('channel_id', $channel->id)
            ->first();

        $payload = [
            'tenant_id'        => $tenantId,
            'channel_id'       => $channel->id,
            'channel_code'     => $channel->code,
            'processing_days'  => (int) ($data['processing_days'] ?? ($existing->processing_days ?? 1)),
            'cutoff_hour'      => (int) ($data['cutoff_hour']     ?? ($existing->cutoff_hour     ?? 22)),
            'cutoff_minute'    => (int) ($data['cutoff_minute']   ?? ($existing->cutoff_minute   ?? 30)),
            'weekly_default'   => $this->normalizeWeekly($data['weekly_default'] ?? null, $existing),
            'blackout_dates'   => $this->parseDateList($data['blackout_dates'] ?? ''),
            'extra_prep_dates' => $this->parseDateList($data['extra_prep_dates'] ?? ''),
        ];

        if ($existing) {
            $existing->fill($payload)->save();
        } else {
            Setting::create($payload);
        }

        return back()->with('success', __('sagawa::app.schedule.saved'));
    }

    /**
     * Accepts the form's weekly_default[] checkbox array. Empty/missing means
     * the admin unchecked everything, which we treat as "no prep ever" — but
     * to avoid foot-guns we save Mon-Fri instead. Admin can still blackout
     * specific dates if they really want to pause shipping entirely.
     */
    private function normalizeWeekly(?array $raw, ?Setting $existing): array
    {
        if ($raw === null) {
            return is_array($existing?->weekly_default) && $existing->weekly_default !== []
                ? array_map('intval', $existing->weekly_default)
                : [1, 2, 3, 4, 5];
        }

        $clean = array_values(array_unique(array_map('intval', $raw)));

        return $clean !== [] ? $clean : [1, 2, 3, 4, 5];
    }

    /**
     * Parse a textarea/CSV of YYYY-MM-DD into a clean sorted unique list.
     */
    private function parseDateList(string $raw): array
    {
        $parts = preg_split('/[\s,]+/', trim($raw)) ?: [];

        $valid = [];
        foreach ($parts as $p) {
            if ($p === '') continue;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $p)) {
                $valid[$p] = true;
            }
        }

        $out = array_keys($valid);
        sort($out);

        return $out;
    }

    private function currentTenantId(): string
    {
        if (function_exists('tenant') && tenant()) {
            return (string) tenant()->id;
        }

        return 'central';
    }
}
