@php
    if (optional(\Route::current())->getName() !== 'shop.home.index') return;

    $channel  = core()->getCurrentChannel();
    $hostname = trim((string) ($channel->hostname ?: request()->getHttpHost()), " \t\n\r/");
    if (! preg_match('#^https?://#i', $hostname)) {
        $hostname = request()->getScheme().'://'.$hostname;
    }
    $base     = rtrim($hostname, '/');
    $logoUrl  = $channel->logo_url ?? null;

    $orgData = [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => 'Fujiba Elegance Emporium',
        'url'      => $base.'/',
        'sameAs'   => [],
    ];
    if ($logoUrl) {
        $orgData['logo'] = $logoUrl;
    }
@endphp

<script type="application/ld+json">{!! json_encode($orgData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
