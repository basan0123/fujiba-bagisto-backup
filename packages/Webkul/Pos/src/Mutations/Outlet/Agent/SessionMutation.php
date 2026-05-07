<?php

namespace Webkul\Pos\Mutations\Outlet\Agent;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\UserChannelMap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\GraphQLAPI\Validators\CustomException;

class SessionMutation extends Controller
{
    public function __construct()
    {
        Auth::shouldUse('pos');
    }

    public function login($rootValue, array $args, GraphQLContext $context): array
    {
        $validator = Validator::make($args, [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors'  => json_encode($validator->errors()->get('*')),
            ];
        }

        $input    = $args['username'];
        $password = $args['password'];

        $tenant = $this->resolveTenantFromMap($input)
               ?? $this->resolveTenantByScan($input);

        if (! $tenant) {
            return [
                'success' => false,
                'message' => trans('pos::app.outlet.agents.login.invalid-credentials'),
            ];
        }

        try {
            tenancy()->initialize($tenant);

            try {
                // Verify credentials against pos_users in tenant DB
                $posUser = \Webkul\Pos\Models\User::where(function ($q) use ($input) {
                    $q->where('username', $input)
                      ->orWhere('email', $input);
                })->first();

                if (! $posUser) {
                    return [
                        'success' => false,
                        'message' => trans('pos::app.outlet.agents.login.invalid-credentials'),
                    ];
                }

                if (! \Hash::check($password, $posUser->password)) {
                    return [
                        'success' => false,
                        'message' => trans('pos::app.outlet.agents.login.invalid-credentials'),
                    ];
                }

                if (! $posUser->status) {
                    return [
                        'success' => false,
                        'message' => trans('pos::app.outlet.agents.login.not-activated'),
                    ];
                }

                // Issue JWT token with tenant_id claim
                $claims = [
                    'tenant_id' => $tenant->getTenantKey(),
                ];

                $originalSecret = config('jwt.secret');
                config(['jwt.secret' => config('pos.jwt_secret') ?: $originalSecret]);

                try {
                    \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::factory()->setTTL((int) config('pos.token_ttl', 480));
                    $token = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::claims($claims)->fromUser($posUser);
                } finally {
                    config(['jwt.secret' => $originalSecret]);
                }

                Event::dispatch('pos.outlet.agents.login.before', $posUser);

                return [
                    'success'      => true,
                    'message'      => trans('pos::app.outlet.agents.login.success'),
                    'access_token' => $token,
                    'agent'        => $posUser,
                ];

            } catch (\Exception $e) {
                throw new CustomException($e->getMessage());
            } finally {
                tenancy()->end();
            }

        } catch (CustomException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new CustomException($e->getMessage());
        }
    }

    public function currentAgent(): mixed
    {
        return auth('pos')->user();
    }

    public function logout(): array
    {
        if (! auth('pos')->check()) {
            throw new CustomException(trans('pos::app.outlet.agents.logout.no-login-agent'));
        }

        $agent = auth('pos')->user();

        auth('pos')->logout();

        // Listener payload is a scalar int (pos_users.id), not an Event object.
        // Listener signature: handle(int $agentId).
        Event::dispatch('pos.outlet.agents.logout.after', $agent->id);

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.agents.logout.success'),
        ];
    }

    /**
     * Fast path: look up tenant via user_channel_map using the central DB.
     * Works when the user has been backfilled or registered through the new flow.
     */
    private function resolveTenantFromMap(string $input): ?Tenant
    {
        $map = UserChannelMap::on('mysql')
            ->active()
            ->whereHas('user', fn ($q) => $q->where('email', $input))
            ->with('tenant')
            ->orderByDesc('updated_at')
            ->first();

        return $map?->tenant;
    }

    /**
     * Slow-path fallback: scan each tenant DB for a matching pos_users row.
     * Used when user_channel_map has no entry (e.g. legacy users not yet backfilled).
     */
    private function resolveTenantByScan(string $input): ?Tenant
    {
        foreach (Tenant::all() as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $exists = \Webkul\Pos\Models\User::where('username', $input)
                    ->orWhere('email', $input)
                    ->exists();

                if ($exists) {
                    return $tenant;
                }
            } catch (\Throwable) {
                // Tenant DB unavailable — skip.
            } finally {
                tenancy()->end();
            }
        }

        return null;
    }
}
