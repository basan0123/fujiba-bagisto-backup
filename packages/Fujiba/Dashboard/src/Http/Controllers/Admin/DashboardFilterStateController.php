<?php

namespace Fujiba\Dashboard\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Dashboard-scoped filter persistence.
 *
 * Stores ONLY: channel, mode, start, end.
 * Deliberately does NOT touch the sales/customers/products list-page
 * datagrid filter state — those are core Bagisto datagrids and changing
 * how filters persist there would affect every staff member on every
 * page. This keeps the blast radius to dashboard widgets only.
 *
 * Per-admin via session key namespaced by auth('admin')->id().
 */
class DashboardFilterStateController extends Controller
{
    private const ALLOWED_KEYS = ['channel', 'mode', 'start', 'end'];

    private const ALLOWED_MODES = ['orders', 'payments'];

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'filters' => session($this->key(), $this->defaults()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $current = session($this->key(), $this->defaults());
        $next    = $current;

        $payload = $request->all();

        foreach (self::ALLOWED_KEYS as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if ($key === 'mode') {
                $value = in_array($value, self::ALLOWED_MODES, true) ? $value : 'orders';
            } elseif ($key === 'channel') {
                $value = is_string($value) ? substr($value, 0, 64) : '';
            } elseif (in_array($key, ['start', 'end'], true)) {
                $value = is_string($value) ? substr($value, 0, 32) : '';
            }

            $next[$key] = $value;
        }

        session([$this->key() => $next]);

        return response()->json([
            'filters' => $next,
        ]);
    }

    private function key(): string
    {
        $adminId = optional(auth('admin')->user())->id ?? 'guest';

        return "fujiba.dashboard.filters.{$adminId}";
    }

    private function defaults(): array
    {
        return [
            'channel' => '',
            'mode'    => 'orders',
            'start'   => '',
            'end'     => '',
        ];
    }
}
