<?php

namespace Fujiba\AuthGateway\Services;

use Illuminate\Support\Facades\DB;

/**
 * Light wrapper over the gateway_signup_attempts table.
 * Uses raw SQL counts so we don't need a model for what's essentially an
 * append-only audit log.
 */
class RateLimiter
{
    public function recordSignup(?string $email, string $ip, ?string $ua, bool $succeeded, ?string $failureReason = null): void
    {
        DB::table('gateway_signup_attempts')->insert([
            'email'          => $email,
            'ip'             => $ip,
            'user_agent'     => $ua ? substr($ua, 0, 500) : null,
            'succeeded'      => $succeeded ? 1 : 0,
            'failure_reason' => $failureReason,
            'created_at'     => now(),
        ]);
    }

    public function signupOverLimit(string $ip, int $perHour, int $perDay): ?string
    {
        $hour = DB::table('gateway_signup_attempts')
            ->where('ip', $ip)
            ->where('failure_reason', '!=', 'login_failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($hour >= $perHour) {
            return "Too many signups from this network in the last hour ({$hour}/{$perHour}). Try again later.";
        }

        $day = DB::table('gateway_signup_attempts')
            ->where('ip', $ip)
            ->where('failure_reason', '!=', 'login_failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($day >= $perDay) {
            return "Too many signups from this network today ({$day}/{$perDay}). Try again tomorrow.";
        }

        return null;
    }

    public function loginLockoutMessage(string $email, int $failsIn15min, int $hardLockout): ?string
    {
        $recent = DB::table('gateway_signup_attempts')
            ->where('email', $email)
            ->where('failure_reason', 'login_failed')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recent >= $hardLockout) {
            return "Account temporarily locked due to too many failed attempts. Try again in 15 minutes.";
        }

        if ($recent >= $failsIn15min) {
            return "Too many failed attempts. Slow down or use forgot-password.";
        }

        return null;
    }

    public function recordLoginFailure(string $email, string $ip, ?string $ua): void
    {
        $this->recordSignup($email, $ip, $ua, false, 'login_failed');
    }

    public function forgotPasswordOverLimit(string $email, int $perHour): bool
    {
        $count = DB::table('gateway_signup_attempts')
            ->where('email', $email)
            ->where('failure_reason', 'forgot_password')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $count >= $perHour;
    }

    public function recordForgotPassword(string $email, string $ip): void
    {
        DB::table('gateway_signup_attempts')->insert([
            'email'          => $email,
            'ip'             => $ip,
            'user_agent'     => null,
            'succeeded'      => 1, // always "succeeds" since we never leak account existence
            'failure_reason' => 'forgot_password',
            'created_at'     => now(),
        ]);
    }
}
