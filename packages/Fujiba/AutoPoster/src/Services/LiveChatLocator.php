<?php

namespace Fujiba\AutoPoster\Services;

use Fujiba\LiveChat\Models\LiveChatSetting;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reads the LiveChat/ChatPion config for a channel — safely.
 *
 * Why this exists rather than calling LiveChatSetting directly:
 *
 * livechat_settings lives ONLY on the central database, but
 * LiveChatSetting hardcodes `protected $connection = 'mysql'`, and under
 * stancl/tenancy the 'mysql' connection is swapped to point at the CURRENT
 * TENANT once a tenant context is initialised. So the same model resolves to
 * central from a central-context request and to the tenant DB from a
 * tenant-context one, where the table does not exist and the query throws
 * "Base table or view not found".
 *
 * Auto Poster's admin screens run in tenant context, so every read here is
 * wrapped: a missing table degrades to "ChatPion not configured", which the
 * pre-flight check already reports as a warning. Losing the ChatPion status
 * must never take down the settings page or block posting.
 *
 * Fixing LiveChatSetting itself would mean touching a live production model
 * used by the running auto-flow dispatcher, so it is deliberately left alone.
 */
class LiveChatLocator
{
    public function forChannel(int $channelId): ?LiveChatSetting
    {
        foreach ($this->candidateConnections() as $connection) {
            try {
                $query = LiveChatSetting::query();

                if ($connection !== null) {
                    $query->on($connection);
                }

                $setting = $query->where('channel_id', $channelId)->first();

                if ($setting) {
                    return $setting;
                }
            } catch (Throwable $e) {
                // Table absent on this connection, or the connection is not
                // configured. Try the next one.
                continue;
            }
        }

        return null;
    }

    /**
     * Whether ChatPion will greet a customer arriving from a post.
     *
     * @return array{configured:bool,enabled:bool,auto_flow:bool,postback_id:?string}
     */
    public function status(int $channelId): array
    {
        $setting = $this->forChannel($channelId);

        if (! $setting) {
            return ['configured' => false, 'enabled' => false, 'auto_flow' => false, 'postback_id' => null];
        }

        return [
            'configured'  => true,
            'enabled'     => (bool) $setting->livechat_enabled,
            'auto_flow'   => (bool) $setting->auto_flow_enabled && ! empty($setting->auto_flow_postback_id),
            'postback_id' => $setting->auto_flow_postback_id,
        ];
    }

    /**
     * Connections to try, in order: the model's own default (correct in central
     * context), then the explicit central connection (correct in tenant
     * context). Null means "leave the model's default alone".
     *
     * @return array<int,string|null>
     */
    protected function candidateConnections(): array
    {
        $candidates = [null];

        foreach (['central', 'mysql_central'] as $name) {
            if (config("database.connections.{$name}")) {
                $candidates[] = $name;
            }
        }

        return $candidates;
    }
}
