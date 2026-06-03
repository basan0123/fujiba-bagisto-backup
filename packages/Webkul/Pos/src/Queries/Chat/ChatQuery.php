<?php

namespace Webkul\Pos\Queries\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Services\PosMessengerService;

/**
 * POS Messenger reads. Everything is scoped to the logged-in agent's
 * outlet → channel → Facebook page. POS chat is Messenger-only: every query
 * proxies the existing Fujiba\LiveChat / Chatpion stack via PosMessengerService.
 *
 * Conversation ids ARE Messenger PSIDs (plain strings) — there is no longer a
 * local chat table, so no dual-path / "mg:" prefix logic.
 */
class ChatQuery extends Controller
{
    public function __construct(protected PosMessengerService $messenger = new PosMessengerService) {}

    /** Resolve the agent's channel; conversations are scoped to it. */
    private function channelId(GraphQLContext $context): ?int
    {
        $cid = $context->user()->outlet?->channel_id;

        return $cid !== null ? (int) $cid : null;
    }

    /** Inbox list, newest activity first. */
    public function conversations($root, array $args, GraphQLContext $context): array
    {
        $channelId = $this->channelId($context);
        $first     = max(1, min(100, (int) ($args['first'] ?? 50)));
        $search    = trim((string) ($args['search'] ?? ''));

        $rows = $this->messenger->conversations($channelId, $search, $first);

        return array_map(fn ($r) => (object) $r, $rows);
    }

    /** One conversation by PSID. */
    public function conversation($root, array $args, GraphQLContext $context)
    {
        $channelId = $this->channelId($context);
        $psid      = (string) $args['id'];

        $row = $this->messenger->conversation($channelId, $psid);

        return $row ? (object) $row : null;
    }

    /** Thread messages (oldest-first); client appends beyond sinceCount. */
    public function messages($root, array $args, GraphQLContext $context): array
    {
        $channelId = $this->channelId($context);
        $psid      = (string) $args['conversationId'];
        $since     = (int) ($args['sinceId'] ?? 0);

        $rows = $this->messenger->messages($channelId, $psid, $since);

        return array_map(fn ($r) => (object) $r, $rows);
    }

    /** Sendable Chatpion bot flows for the channel's page. */
    public function chatpionFlows($root, array $args, GraphQLContext $context): array
    {
        return $this->messenger->botFlows($this->channelId($context));
    }

    /** Canned response templates for the channel's page. */
    public function cannedResponses($root, array $args, GraphQLContext $context): array
    {
        return array_map(fn ($r) => (object) $r, $this->messenger->cannedResponses($this->channelId($context)));
    }

    /** Whether Messenger is configured for the agent's channel. */
    public function messengerEnabled($root, array $args, GraphQLContext $context): bool
    {
        return $this->messenger->isConfigured($this->channelId($context));
    }

    /**
     * Find the Messenger conversation for a customer or order, so the POS can
     * auto-open the thread after a sale or from an order detail. Resolves the
     * subscriber PSID from customers.chatpion_subscribe_id and returns the
     * conversation snapshot, or null when the customer isn't linked to FB.
     */
    public function findThread($root, array $args, GraphQLContext $context)
    {
        $channelId = $this->channelId($context);
        if ($channelId === null) {
            return null;
        }

        $customerId = $args['customerId'] ?? null;

        // Order → its customer.
        if (! $customerId && ! empty($args['orderId'])) {
            $customerId = DB::table('orders')
                ->where('id', (int) $args['orderId'])
                ->value('customer_id');
        }

        if (! $customerId) {
            return null;
        }

        $psid = DB::table('customers')
            ->where('id', (int) $customerId)
            ->value('chatpion_subscribe_id');

        if (! $psid) {
            return null;
        }

        $row = $this->messenger->conversation($channelId, (string) $psid);

        return $row ? (object) $row : null;
    }

    /** POS chat is Messenger-only. */
    public function resolveSource($root): string
    {
        return 'messenger';
    }
}
