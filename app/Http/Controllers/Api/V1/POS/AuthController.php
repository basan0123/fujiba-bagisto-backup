<?php

// app/Http/Controllers/Api/V1/POS/AuthController.php

namespace App\Http\Controllers\Api\V1\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosLoginRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserChannelMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(PosLoginRequest $request): JsonResponse
    {
        $email = $request->email;

        // Step 1: Try to resolve tenant from user_channel_map (fast path).
        // user_channel_map.user_id references central users.id, so join on email.
        $tenant   = null;
        $channelId           = null;
        $inventorySourceId   = null;

        $centralUser = User::where('email', $email)->first();

        if ($centralUser) {
            $mapping = UserChannelMap::on('mysql')
                ->where('user_id', $centralUser->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderByDesc('updated_at')
                ->first();

            if ($mapping) {
                $tenant              = Tenant::find($mapping->tenant_id);
                $channelId           = $mapping->channel_id;
                $inventorySourceId   = $mapping->inventory_source_id;
            }
        }

        // Step 2: Slow-path — scan every tenant DB for a matching pos_users row.
        if (! $tenant) {
            foreach (Tenant::all() as $candidate) {
                try {
                    tenancy()->initialize($candidate);

                    $exists = \Webkul\Pos\Models\User::where('email', $email)->exists();

                    tenancy()->end();

                    if ($exists) {
                        $tenant = $candidate;
                        break;
                    }
                } catch (\Throwable) {
                    tenancy()->end();
                }
            }
        }

        if (! $tenant) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        // Step 3: Initialize tenant, authenticate against tenant pos_users.
        try {
            tenancy()->initialize($tenant);
        } catch (\Throwable) {
            return response()->json(['error' => 'tenant_unavailable'], 503);
        }

        try {
            $posUser = \Webkul\Pos\Models\User::where('email', $email)->first();

            if (! $posUser) {
                return response()->json(['error' => 'Unauthorized.'], 401);
            }

            if (! Hash::check($request->password, $posUser->password)) {
                return response()->json(['error' => 'Unauthorized.'], 401);
            }

            if (! $posUser->status) {
                return response()->json(['error' => 'pos_user_inactive'], 403);
            }

            // Resolve channel_id / inventory_source_id from tenant DB if not
            // already known from user_channel_map.
            if (! $channelId || ! $inventorySourceId) {
                $outlet = \DB::table('pos_outlets')->where('id', $posUser->outlet_id)->first();

                if ($outlet) {
                    $inventorySourceId = $outlet->inventory_source_id;

                    // Prefer the outlet's OWN channel_id column — it is the
                    // unambiguous source of truth. The channel_inventory_sources
                    // lookup is ambiguous on Fujiba: kimono (1) and mmfood (2)
                    // both share inventory_source_id=1, so that table returns
                    // channel 1 for BOTH outlets and the mmfood agent was being
                    // handed a kimono JWT (seeing kimono products on home).
                    //
                    // The pos_outlets.channel_id column only exists on tenants
                    // migrated after 2026-05-18 — older clones (e.g. a6) lack it,
                    // so guard with property_exists before reading to avoid an
                    // "Undefined property" notice, then fall back to the
                    // source→channel map (safe for single-channel tenants).
                    $outletChannelId = property_exists($outlet, 'channel_id') ? $outlet->channel_id : null;

                    if (! empty($outletChannelId)) {
                        $channelId = (int) $outletChannelId;
                    } else {
                        $cis = \DB::table('channel_inventory_sources')
                            ->where('inventory_source_id', $inventorySourceId)
                            ->first();

                        $channelId = $cis ? $cis->channel_id : 1;
                    }
                } else {
                    // Fallback to tenant's default channel
                    $channelId         = \DB::table('channels')->value('id') ?? 1;
                    $inventorySourceId = \DB::table('inventory_sources')->value('id') ?? 1;
                }
            }

            $channel = \DB::table('channels')->where('id', $channelId)->first();

            $claims = [
                'tenant_id'           => $tenant->getTenantKey(),
                'channel_id'          => $channelId,
                'inventory_source_id' => $inventorySourceId,
            ];

            $originalSecret = config('jwt.secret');
            config(['jwt.secret' => config('pos.jwt_secret') ?: $originalSecret]);

            try {
                JWTAuth::factory()->setTTL((int) config('pos.token_ttl', 480));
                $token = JWTAuth::claims($claims)->fromUser($posUser);
            } finally {
                config(['jwt.secret' => $originalSecret]);
            }

            return response()->json([
                'token'               => $token,
                'tenant_id'           => $tenant->getTenantKey(),
                'channel_id'          => $channelId,
                'channel_code'        => $channel->code ?? 'default',
                'inventory_source_id' => $inventorySourceId,
                'pos_url'             => rtrim(config('app.url'), '/') . '/pos',
            ]);

        } catch (\Throwable $e) {
            return response()->json(['error' => 'server_error', 'message' => $e->getMessage()], 500);
        } finally {
            tenancy()->end();
        }
    }

    public function me(): JsonResponse
    {
        try {
            $payload   = JWTAuth::parseToken()->getPayload();
            $tenantId  = $payload->get('tenant_id');
            $channelId = $payload->get('channel_id');

            $tenant = Tenant::find($tenantId);

            if (! $tenant) {
                return response()->json(['error' => 'tenant_not_found'], 401);
            }

            try {
                tenancy()->initialize($tenant);
            } catch (\Throwable) {
                return response()->json(['error' => 'tenant_unavailable'], 401);
            }

            try {
                $posUser = auth('pos')->userOrFail();

                return response()->json([
                    'id'                  => $posUser->id,
                    'email'               => $posUser->email,
                    'firstname'           => $posUser->firstname,
                    'lastname'            => $posUser->lastname,
                    'username'            => $posUser->username,
                    'status'              => $posUser->status,
                    'image_url'           => $posUser->image_url,
                    'tenant_id'           => $tenantId,
                    'channel_id'          => $channelId,
                    'inventory_source_id' => $payload->get('inventory_source_id'),
                ]);
            } finally {
                tenancy()->end();
            }
        } catch (\Throwable) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }
    }
}

