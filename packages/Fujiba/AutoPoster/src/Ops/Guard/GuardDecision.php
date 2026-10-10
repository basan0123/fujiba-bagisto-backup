<?php

namespace Fujiba\AutoPoster\Ops\Guard;

final class GuardDecision
{
    private function __construct(
        public readonly bool $allowed,
        public readonly ?string $reason,
        public readonly string $detail,
        public readonly ?float $load,
        public readonly ?float $siteMs,
        public readonly ?float $baselineMs,
    ) {}

    public static function allow(?float $load, ?float $ms, ?float $baseline): self
    {
        return new self(true, null, 'ok', $load, $ms, $baseline);
    }

    public static function deny(string $reason, string $detail, ?float $load, ?float $ms = null, ?float $baseline = null): self
    {
        return new self(false, $reason, $detail, $load, $ms, $baseline);
    }

    public function toArray(): array
    {
        return ['allowed' => $this->allowed, 'reason' => $this->reason, 'detail' => $this->detail,
            'load' => $this->load, 'site_ms' => $this->siteMs, 'baseline_ms' => $this->baselineMs];
    }
}
