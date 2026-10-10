<?php

namespace Fujiba\AutoPoster\Ops\Insights;

use Illuminate\Support\Facades\Http;

class HttpGraphClient implements GraphClient
{
    public function __construct(private string $version = 'v25.0', private string $base = 'https://graph.facebook.com') {}

    public function get(string $path, array $query, string $token): array
    {
        $url = rtrim($this->base, '/').'/'.$this->version.'/'.ltrim($path, '/');
        // Token in Authorization header, never in the URL (keeps it out of logs).
        $res = Http::timeout(20)->withToken($token)->acceptJson()->get($url, $query);
        $body = $res->json() ?? [];

        if ($res->failed() || isset($body['error'])) {
            $err = $body['error'] ?? [];
            throw new GraphException(
                substr((string) ($err['message'] ?? 'graph http '.$res->status()), 0, 300),
                isset($err['code']) ? (int) $err['code'] : null,
                $res->status(),
            );
        }

        return $body;
    }
}
