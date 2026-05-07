<?php

namespace Webkul\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Database\Models\Domain;
use Webkul\Pos\Models\CustomerDisplaySession;

class CustomerDisplayController extends Controller
{
    public function __construct()
    {
        Auth::shouldUse('pos');
    }

    public function index()
    {
        $agent = auth('pos')->user();
        abort_unless($agent, 401);

        $now = now();

        $sessions = CustomerDisplaySession::where('agent_id', $agent->id)
            ->whereNull('revoked_at')
            ->orderByDesc('id')
            ->get(['id', 'paired_device_label', 'paired_device_ua', 'last_seen_at', 'paired_device_ip', 'created_at']);

        return response()->json([
            'sessions' => $sessions->map(fn ($s) => [
                'id'                  => $s->id,
                'paired_device_label' => $s->paired_device_label,
                'paired_device_ua'    => $s->paired_device_ua,
                'paired_device_ip'    => $s->paired_device_ip,
                'last_seen_at'        => $s->last_seen_at?->toIso8601String(),
                'paired_at'           => $s->created_at?->toIso8601String(),
                'online'              => $s->last_seen_at !== null
                    && $s->last_seen_at->gte($now->copy()->subSeconds(45)),
            ])->all(),
        ]);
    }

    public function pair(Request $request)
    {
        $request->validate([
            'terminal_uuid' => 'nullable|string|max:64',
        ]);

        $agent = auth('pos')->user();
        abort_unless($agent, 401);
        abort_unless($agent->outlet_id, 423, 'Agent has no outlet binding');

        $terminalUuid = $request->input('terminal_uuid')
            ?: hash('sha256', $request->userAgent().'|'.$request->ip());

        // No expires_at gate — sessions live until manually revoked.
        // The expires_at column is kept (set far in the future) so we can
        // re-introduce TTL policy later without a schema change.
        //
        // FUJIBA 2026-05-06: lookup keyed on (tenant_id, terminal_uuid) only.
        // Any cashier signing in on the same terminal re-uses the same row
        // and the same /pos/display/{token} URL — the customer screen never
        // has to be re-paired across staff handoffs. agent_id is updated in
        // place so Settings → Customer Display still lists it under the
        // current cashier.
        $existing = CustomerDisplaySession::where('tenant_id', tenant()->id)
            ->where('terminal_uuid', $terminalUuid)
            ->whereNull('revoked_at')
            ->first();

        if ($existing) {
            if ($existing->agent_id !== $agent->id || $existing->outlet_id !== $agent->outlet_id) {
                $existing->update([
                    'agent_id'  => $agent->id,
                    'outlet_id' => $agent->outlet_id,
                ]);
            }

            return response()->json([
                'id'         => $existing->id,
                'token'      => $existing->pairing_token,
                'url'        => $this->displayUrl($existing->pairing_token, $existing->tenant_id),
                'expires_at' => $existing->expires_at,
            ]);
        }

        $row = CustomerDisplaySession::create([
            'tenant_id'      => tenant()->id,
            'agent_id'       => $agent->id,
            'outlet_id'      => $agent->outlet_id,
            'terminal_uuid'  => $terminalUuid,
            'pairing_token'  => self::mintToken(),
            'expires_at'     => now()->addYears(100),
        ]);

        return response()->json([
            'id'         => $row->id,
            'token'      => $row->pairing_token,
            'url'        => $this->displayUrl($row->pairing_token, $row->tenant_id),
            'expires_at' => $row->expires_at,
        ], 201);
    }

    /**
     * Build a pairing URL whose Host matches the row's tenant.
     * Public display routes resolve tenancy by Host; if we used url() we'd
     * inherit the JWT-driven request host, which may not be the tenant's
     * registered domain. Look up the canonical domain from the central DB.
     */
    private function displayUrl(string $token, string $tenantId): string
    {
        // TODO(slice-3): Cache::remember("tenant_domain.{$tenantId}", 300, ...)
        // — central DB hit per pair() is fine at low volume but cheap to fix.
        $domain = Domain::on(config('tenancy.database.central_connection'))
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->value('domain');

        $scheme = config('app.force_https') || request()->isSecure() ? 'https' : 'http';

        return $domain
            ? "{$scheme}://{$domain}/pos/display/{$token}"
            : url("/pos/display/{$token}");
    }

    public function heartbeat(Request $request, int $id)
    {
        // POS cart is offline-first (IndexedDB). The cashier's browser is the
        // source of truth for totals during the building of an order — there
        // is no server-side cart row until checkout. Accept totals from the
        // client. Customer display matches whatever the cashier sees.
        $data = $request->validate([
            'subtotal' => 'required|numeric',
            'tax'      => 'required|numeric',
            'total'    => 'required|numeric',
            'items'    => 'required|integer|min:0',
            'currency' => 'nullable|string|size:3',
            'state'    => 'nullable|in:cart,paid',
        ]);

        $agent = auth('pos')->user();
        abort_unless($agent, 401);

        $row = CustomerDisplaySession::where('id', $id)
            ->where('agent_id', $agent->id)
            ->whereNull('revoked_at')
            ->firstOrFail();

        $payload = [
            'subtotal' => (float) $data['subtotal'],
            'tax'      => (float) $data['tax'],
            'total'    => (float) $data['total'],
            'items'    => (int) $data['items'],
            'currency' => $data['currency'] ?? 'JPY',
            'state'    => $data['state'] ?? 'cart',
            'ts'       => now()->getTimestampMs(),
        ];

        $row->update([
            'last_payload'    => $payload,
            'last_payload_at' => now(),
        ]);

        return response()->json(['ok' => true, 'payload' => $payload]);
    }

    public function revoke(int $id)
    {
        $agent = auth('pos')->user();
        abort_unless($agent, 401);

        $row = CustomerDisplaySession::where('id', $id)
            ->where('agent_id', $agent->id)
            ->whereNull('revoked_at')
            ->firstOrFail();

        $row->update([
            'revoked_at'     => now(),
            'revoked_reason' => 'manual',
        ]);

        return response()->noContent();
    }

    private static function mintToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }
}
