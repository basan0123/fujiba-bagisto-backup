<?php

namespace Fujiba\AuthGateway\Services;

use Illuminate\Support\Str;

class Slugger
{
    public function __construct(
        protected array $reserved = [],
        protected int   $maxLength = 40,
        protected int   $suffixRetries = 5
    ) {}

    /**
     * Slugify store_name → ascii dashes lowercase. Cap at maxLength.
     * Returns the BASE slug without collision-suffix.
     */
    public function baseSubdomain(string $storeName): string
    {
        $slug = Str::ascii($storeName);
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '') {
            // pure non-ascii input — fall back
            $slug = 'store';
        }

        if (strlen($slug) > $this->maxLength) {
            $slug = substr($slug, 0, $this->maxLength);
            $slug = trim($slug, '-');
        }

        // single character / pure-digit slugs get a 'store-' prefix to avoid
        // ambiguity with future routes
        if (strlen($slug) < 3 || ctype_digit($slug)) {
            $slug = 'store-' . $slug;
            $slug = substr($slug, 0, $this->maxLength);
            $slug = trim($slug, '-');
        }

        return $slug;
    }

    public function isReserved(string $slug): bool
    {
        return in_array($slug, $this->reserved, true);
    }

    public function suffix(int $length = 4): string
    {
        return strtolower(Str::random($length));
    }

    /**
     * Try base, then base-XXXX up to suffixRetries times. The collision-check
     * callback receives a candidate and returns true if it is FREE.
     */
    public function nextAvailable(string $storeName, callable $isFree): string
    {
        $base = $this->baseSubdomain($storeName);

        if ($this->isReserved($base) === false && $isFree($base)) {
            return $base;
        }

        for ($i = 0; $i < $this->suffixRetries; $i++) {
            $candidate = $base . '-' . $this->suffix();
            // Re-trim in case suffix tipped us over the limit
            $candidate = substr($candidate, 0, $this->maxLength);
            if ($this->isReserved($candidate)) {
                continue;
            }
            if ($isFree($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException("Could not allocate a free subdomain after {$this->suffixRetries} attempts (base: {$base})");
    }
}
