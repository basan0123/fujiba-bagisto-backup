<?php

namespace Fujiba\SagawaDelivery\Http\Controllers\Admin;

use Fujiba\SagawaDelivery\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CredentialController extends Controller
{
    public function index()
    {
        $tenantId = $this->currentTenantId();

        $channels = core()->getAllChannels();

        $credentials = Credential::query()
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('channel_id');

        return view('sagawa::admin.credentials.index', compact('channels', 'credentials', 'tenantId'));
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'channel_id'        => 'required|integer',
            'api_customer_id'   => 'nullable|string|max:255',
            'api_password'      => 'nullable|string|max:255',
            'api_endpoint'      => 'nullable|string|max:255',
            'use_sandbox'       => 'nullable|boolean',
            'enabled'           => 'nullable|boolean',
            'use_sagawa_rate'   => 'nullable|boolean',
            'priority'          => 'nullable|integer|min:1|max:999',
            'preparation_days'  => 'nullable|integer|min:0|max:60',
            'sender_name'       => 'nullable|string|max:255',
            'sender_postcode'   => 'nullable|string|max:20',
            'sender_prefecture' => 'nullable|string|max:100',
            'sender_city'       => 'nullable|string|max:100',
            'sender_address'    => 'nullable|string|max:255',
            'sender_phone'      => 'nullable|string|max:50',
            'support_phone'     => 'nullable|string|max:50',
            'support_hours'     => 'nullable|string|max:100',
            'support_branch'    => 'nullable|string|max:255',
        ]);

        $tenantId = $this->currentTenantId();

        $channel = core()->getAllChannels()->firstWhere('id', (int) $data['channel_id']);

        if (! $channel) {
            return back()->with('error', __('sagawa::app.credentials.channel-not-found'));
        }

        $existing = Credential::where('tenant_id', $tenantId)
            ->where('channel_id', $channel->id)
            ->first();

        $payload = [
            'tenant_id'         => $tenantId,
            'channel_id'        => $channel->id,
            'channel_code'      => $channel->code,
            'api_endpoint'      => $data['api_endpoint']      ?? ($existing->api_endpoint ?? 'https://api.sgsystems.co.jp'),
            'use_sandbox'       => (bool) ($data['use_sandbox']      ?? false),
            'enabled'           => (bool) ($data['enabled']          ?? false),
            'use_sagawa_rate'   => (bool) ($data['use_sagawa_rate']  ?? false),
            'priority'          => (int)  ($data['priority']         ?? ($existing->priority         ?? 20)),
            'preparation_days'  => (int)  ($data['preparation_days'] ?? ($existing->preparation_days ?? 1)),
            'sender_name'       => $data['sender_name']       ?? ($existing->sender_name       ?? ''),
            'sender_postcode'   => $data['sender_postcode']   ?? ($existing->sender_postcode   ?? ''),
            'sender_prefecture' => $data['sender_prefecture'] ?? ($existing->sender_prefecture ?? ''),
            'sender_city'       => $data['sender_city']       ?? ($existing->sender_city       ?? ''),
            'sender_address'    => $data['sender_address']    ?? ($existing->sender_address    ?? ''),
            'sender_phone'      => $data['sender_phone']      ?? ($existing->sender_phone      ?? ''),
            'support_phone'     => $data['support_phone']     ?? ($existing->support_phone     ?? ''),
            'support_hours'     => $data['support_hours']     ?? ($existing->support_hours     ?? ''),
            'support_branch'    => $data['support_branch']    ?? ($existing->support_branch    ?? ''),
        ];

        // Only overwrite credentials if non-empty (empty means "keep existing")
        if (! empty($data['api_customer_id'])) {
            $payload['api_customer_id'] = $data['api_customer_id'];
        } elseif ($existing) {
            // keep existing value
        } else {
            $payload['api_customer_id'] = '';
        }

        if (! empty($data['api_password'])) {
            $payload['api_password'] = $data['api_password'];
        } elseif ($existing) {
            // keep existing value
        } else {
            $payload['api_password'] = '';
        }

        if ($existing) {
            $existing->fill($payload)->save();
        } else {
            Credential::create($payload);
        }

        return back()->with('success', __('sagawa::app.credentials.saved'));
    }

    public function destroy(int $id)
    {
        $tenantId = $this->currentTenantId();

        $credential = Credential::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($credential) {
            $credential->delete();
        }

        return back()->with('success', __('sagawa::app.credentials.deleted'));
    }

    private function currentTenantId(): string
    {
        if (function_exists('tenant') && tenant()) {
            return (string) tenant()->id;
        }

        return 'central';
    }
}
