<?php

namespace Webkul\Pos\Listeners;

use Webkul\Pos\Models\CustomerDisplaySession;

class RevokeCustomerDisplaysOnLogout
{
    /**
     * Event payload is a scalar int (pos_users.id) per
     * SessionMutation::logout — not an Event object.
     */
    public function handle(int $agentId): void
    {
        try {
            CustomerDisplaySession::where('agent_id', $agentId)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at'     => now(),
                    'revoked_reason' => 'logout',
                ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
