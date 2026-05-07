<?php

namespace Fujiba\AutoLogin\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches a TikTok user profile by open_id.
 *
 * Mirrors the shape of MessengerLoginController's fetchSubscriber():
 * call upstream, return a normalised array, fail soft.
 *
 * Real TikTok integration depends on Sprint 2 — the OAuth credentials
 * and shop API surface land there. For Sprint 1 this service is a stub
 * that returns null on every call so the controller falls back to its
 * placeholder profile shape (matches Messenger's behaviour when its
 * API endpoint is unreachable).
 */
class TikTokProfileService
{
    public function fetch(string $openId): ?array
    {
        // Real call wired up in Sprint 2 once TikTok app credentials exist.
        // Until then: return null so the caller uses placeholder fields.
        return null;
    }
}
