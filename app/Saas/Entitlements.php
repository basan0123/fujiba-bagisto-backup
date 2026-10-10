<?php

namespace App\Saas;

use InvalidArgumentException;

/**
 * Read-only view of what a subscription plan allows, backed by config/saas.php.
 *
 * Enforcement points (product create, staff invite, AI calls…) ask this class
 * instead of hard-coding numbers, so plan changes stay a config edit.
 */
class Entitlements
{
    public function __construct(protected string $planCode, protected array $plan)
    {
    }

    public static function for(string $planCode): self
    {
        $plan = config("saas.plans.{$planCode}");

        if (! is_array($plan)) {
            throw new InvalidArgumentException("Unknown SaaS plan [{$planCode}].");
        }

        return new self($planCode, $plan);
    }

    public function plan(): string
    {
        return $this->planCode;
    }

    /** null = unlimited. */
    public function limit(string $key): ?int
    {
        if (! array_key_exists($key, $this->plan['limits'] ?? [])) {
            throw new InvalidArgumentException("Unknown SaaS limit [{$key}].");
        }

        return $this->plan['limits'][$key];
    }

    /** Whether one more unit fits, given current usage. */
    public function allows(string $key, int $currentUsage, int $adding = 1): bool
    {
        $limit = $this->limit($key);

        return $limit === null || $currentUsage + $adding <= $limit;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->plan['features'] ?? [], true);
    }
}
