<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoginToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public static function hash(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    /**
     * Issue a fresh one-time login token for the given user.
     * Returns [model, raw token]. Persist the model; hand the raw to the URL.
     */
    public static function issue(int $userId, int $ttlMinutes = 10, ?string $ip = null, ?string $userAgent = null): array
    {
        $raw = Str::random(64);

        $token = static::create([
            'user_id'    => $userId,
            'token_hash' => static::hash($raw),
            'expires_at' => now()->addMinutes($ttlMinutes),
            'ip'         => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
        ]);

        return [$token, $raw];
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    public function markUsed(): void
    {
        $this->used_at = now();
        $this->save();
    }
}
