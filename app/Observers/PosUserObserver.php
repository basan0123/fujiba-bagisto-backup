<?php

// app/Observers/PosUserObserver.php

namespace App\Observers;

use App\Models\User as CentralUser;
use App\Models\UserChannelMap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\Channel;
use Webkul\Pos\Models\User as PosUser;

class PosUserObserver
{
    public function created(PosUser $posUser): void
    {
        try {
            $tenantId = app('tenant')?->id;

            if (! $tenantId) {
                return;
            }

            $centralUser = CentralUser::firstOrCreate(
                ['email' => $posUser->email],
                [
                    'name'     => trim($posUser->firstname . ' ' . $posUser->lastname),
                    'password' => $posUser->getAttributes()['password'] ?? '',
                ]
            );

            $channel = Channel::first();

            if (! $channel) {
                return;
            }

            if (UserChannelMap::where('user_id', $centralUser->id)->where('channel_id', $channel->id)->exists()) {
                return;
            }

            DB::transaction(function () use ($centralUser, $channel, $tenantId, $posUser) {
                UserChannelMap::create([
                    'user_id'             => $centralUser->id,
                    'channel_id'          => $channel->id,
                    'tenant_id'           => $tenantId,
                    'inventory_source_id' => $posUser->outlet?->inventory_source_id,
                    'is_active'           => true,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('PosUserObserver: failed to create user_channel_map', [
                'pos_user_id' => $posUser->id,
                'email'       => $posUser->email,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
