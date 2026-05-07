@php
    /**
     * Canonical + hreflang head injector.
     *
     * Wired via `Event::listen('bagisto.shop.layout.head.after', …)` in
     * PwaFrontendServiceProvider, so this fires inside <head> of every
     * Bagisto storefront page (home, category, product, CMS, search, etc.).
     *
     * Skip injection on routes where canonical/hreflang isn't useful or
     * is actively harmful (admin pages already render their own <head>;
     * checkout pages should not be indexed; search is robots-disallowed).
     */
    $routeName  = optional(\Route::current())->getName();
    $skipRoutes = [
        'shop.checkout.onepage.index',
        'shop.checkout.onepage.success',
        'shop.checkout.cart.index',
        'shop.search.index',
        'shop.compare.index',
    ];
    $skip = in_array($routeName, $skipRoutes, true)
            || str_starts_with((string) $routeName, 'shop.customers.account.')
            || str_starts_with((string) $routeName, 'admin.');
@endphp

@unless ($skip)
    @php
        $channel       = core()->getCurrentChannel();
        $channelLocales= $channel->locales->pluck('code')->all() ?: ['en'];

        // Strip ?locale= and ?currency= from query string for the canonical
        // URL; everything else (filters, pagination) stays in.
        $request   = request();
        $queryArr  = $request->query();
        unset($queryArr['locale'], $queryArr['currency']);
        $cleanPath = $request->path() === '/' ? '' : '/'.ltrim($request->path(), '/');
        $hostname  = trim((string) ($channel->hostname ?: $request->getHttpHost()), " \t\n\r/");
        if (! preg_match('#^https?://#i', $hostname)) {
            $hostname = $request->getScheme().'://'.$hostname;
        }
        $base      = rtrim($hostname, '/');
        $canonical = $base.$cleanPath;
        if (! empty($queryArr)) {
            $canonical .= '?'.http_build_query($queryArr);
        }

        // Hreflang siblings: same canonical URL with `?locale=<code>` added.
        // x-default points at the canonical (no locale param) which Bagisto
        // serves in the channel default locale.
        $localeUrl = function (string $code) use ($canonical) {
            return $canonical.(str_contains($canonical, '?') ? '&' : '?').'locale='.$code;
        };
    @endphp

    <link rel="canonical" href="{{ $canonical }}"/>
    @foreach ($channelLocales as $code)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $localeUrl($code) }}"/>
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $canonical }}"/>
@endunless
