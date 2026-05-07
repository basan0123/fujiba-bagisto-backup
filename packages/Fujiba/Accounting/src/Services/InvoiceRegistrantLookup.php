<?php

namespace Fujiba\Accounting\Services;

use Carbon\Carbon;
use Fujiba\Accounting\Models\InvoiceRegistrant;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Facades\Log;

/**
 * 国税庁 適格請求書発行事業者公表サイト API client.
 *
 * Cache-first: returns rows from `fujiba_invoice_registrants` if `fetched_at`
 * is within the configured window (default 30 days). Otherwise calls the
 * NTA endpoint and persists the result. Without an `app_id` we silently
 * no-op so the rest of the system keeps working.
 *
 * Reference: https://www.invoice-kohyo.nta.go.jp/web-api/
 */
class InvoiceRegistrantLookup
{
    public function __construct(
        protected ?ClientInterface $http = null,
        protected ?CacheFactory $cache = null,
    ) {
        $this->http ??= new Client(['timeout' => 10, 'http_errors' => false]);
        $this->cache ??= app(CacheFactory::class);
    }

    public function lookup(string $tNumber): ?InvoiceRegistrant
    {
        $tNumber = trim($tNumber);
        if (! preg_match('/^T\d{13}$/', $tNumber)) {
            return null;
        }

        $cached = InvoiceRegistrant::find($tNumber);
        $cacheDays = (int) config('accounting.nta_invoice_api.cache_days', 30);
        if ($cached && $cached->fetched_at && $cached->fetched_at->gte(now()->subDays($cacheDays))) {
            return $cached;
        }

        $appId = config('accounting.nta_invoice_api.app_id');
        if (! $appId) {
            Log::warning('Fujiba\\Accounting: NTA invoice API app_id is not configured — skipping live lookup', [
                't_number' => $tNumber,
            ]);
            return $cached;   // may be null
        }

        // Cheap rate limiter: 10 req/sec across the cluster.
        $perSec = (int) config('accounting.nta_invoice_api.rate_limit_per_sec', 10);
        $lock = $this->cache->store()->lock('fujiba.nta-invoice-api.tick', 1);
        if ($perSec > 0) {
            // Sleep up to 100ms × ceil(perSec/10) if the lock is held — naive but adequate.
            $waitMs = (int) ceil(1000 / max($perSec, 1));
            if (! $lock->get()) {
                usleep($waitMs * 1000);
            }
        }

        try {
            $body = $this->fetch($appId, $tNumber);
        } catch (GuzzleException $e) {
            Log::error('Fujiba\\Accounting: NTA invoice API call failed', [
                't_number' => $tNumber,
                'error'    => $e->getMessage(),
            ]);
            return $cached;
        } finally {
            try { $lock->release(); } catch (\Throwable) { /* ignore */ }
        }

        if (! $body || empty($body['announcement']) || ! is_array($body['announcement'])) {
            // API returned no match.
            return $cached;
        }

        $row = $body['announcement'][0] ?? null;
        if (! is_array($row)) {
            return $cached;
        }

        return $this->persist($tNumber, $row, $body);
    }

    protected function fetch(string $appId, string $tNumber): ?array
    {
        $url = (string) config('accounting.nta_invoice_api.base_url');
        if (! $url) {
            return null;
        }

        $resp = $this->http->request('GET', $url, [
            'query' => [
                'id'      => $appId,
                'number'  => $tNumber,
                'type'    => '21',
                'history' => '0',
            ],
            'headers' => ['Accept' => 'application/json'],
        ]);

        if ($resp->getStatusCode() !== 200) {
            Log::warning('Fujiba\\Accounting: NTA invoice API non-200', [
                't_number' => $tNumber,
                'status'   => $resp->getStatusCode(),
            ]);
            return null;
        }

        return json_decode((string) $resp->getBody(), true) ?? null;
    }

    protected function persist(string $tNumber, array $row, array $rawBody): InvoiceRegistrant
    {
        return InvoiceRegistrant::updateOrCreate(
            ['t_number' => $tNumber],
            [
                'name'                 => (string) ($row['name'] ?? ''),
                'kana_name'            => $row['kana'] ?? null,
                'trade_name'           => $row['trade_name'] ?? null,
                'address'              => $row['address'] ?? null,
                'country'              => $row['country'] ?? 'JP',
                'registration_date'    => $this->date($row['registratedDate'] ?? null),
                'disposition_date'     => $this->date($row['disposalDate'] ?? null),
                'process_type'         => $row['process'] ?? null,
                'latest_update_date'   => $row['latestUpdateDate'] ?? null,
                'raw_json'             => $rawBody,
                'fetched_at'           => now(),
            ]
        );
    }

    protected function date(?string $v): ?string
    {
        if (! $v) {
            return null;
        }
        try {
            return Carbon::parse($v)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
