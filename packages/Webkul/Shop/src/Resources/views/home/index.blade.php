@php
    $channel     = core()->getCurrentChannel();
    $channelCode = $channel->code;
    $locale      = app()->getLocale();

    // Fujiba: blocks/slides may carry options.audience = retail|wholesale.
    $audience = auth()->guard('customer')->user()?->group?->code === 'wholesale' ? 'wholesale' : 'retail';
@endphp

@push('meta')
    <meta name="title"       content="{{ $channel->home_seo['meta_title']       ?? '' }}" />
    <meta name="description" content="{{ $channel->home_seo['meta_description'] ?? '' }}" />
    <meta name="keywords"    content="{{ $channel->home_seo['meta_keywords']    ?? '' }}" />

    {{-- Fujiba SEO: social cards + WebSite/OnlineStore schema. --}}
    @php
        $seoTitle = $channel->home_seo['meta_title'] ?? $channel->name;
        $seoDesc  = $channel->home_seo['meta_description'] ?? '';
        $seoImage = $channel->logo_url ?? '';
        $seoUrl   = url('/');
    @endphp
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="{{ $channel->name }}" />
    <meta property="og:title" content="{{ $seoTitle }}" />
    <meta property="og:description" content="{{ $seoDesc }}" />
    <meta property="og:url" content="{{ $seoUrl }}" />
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}" />
    @if ($seoImage)<meta property="og:image" content="{{ $seoImage }}" />@endif
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $seoTitle }}" />
    <meta name="twitter:description" content="{{ $seoDesc }}" />
    @foreach (core()->getCurrentChannel()->locales as $alt)
        <link rel="alternate" hreflang="{{ $alt->code }}" href="{{ $seoUrl }}?locale={{ $alt->code }}" />
    @endforeach
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => [
            ['@type' => 'WebSite', 'name' => $channel->name, 'url' => $seoUrl, 'inLanguage' => app()->getLocale(),
             'potentialAction' => ['@type' => 'SearchAction', 'target' => $seoUrl.'/search?query={search_term_string}', 'query-input' => 'required name=search_term_string']],
            ['@type' => 'OnlineStore', 'name' => $channel->name, 'url' => $seoUrl, 'description' => $seoDesc, 'image' => $seoImage ?: null, 'currenciesAccepted' => 'JPY', 'areaServed' => 'JP'],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush


@push('styles')
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Shippori+Mincho:wght@500;600&display=swap" rel="stylesheet">
<style>
/* Fujiba premium page-load + scroll motion (home only) */
.fj-curtain{position:fixed;inset:0;z-index:9999;pointer-events:none;display:flex}
.fj-curtain i{flex:1;background:linear-gradient(180deg,#3a0a14,#5c0a1a);border-right:1px solid rgba(214,178,110,.5);animation:fjCurtain 1.1s cubic-bezier(.77,0,.18,1) .15s forwards}
.fj-curtain i:nth-child(2){animation-delay:.22s}.fj-curtain i:nth-child(3){animation-delay:.29s}.fj-curtain i:nth-child(4){animation-delay:.36s;border-right:0}
.fj-curtain b{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);font:400 34px/1 'DM Serif Display',serif;letter-spacing:.35em;color:#d6b26e;animation:fjLogo 1s ease forwards}
@keyframes fjCurtain{to{transform:translateY(-101%)}}
@keyframes fjLogo{0%{opacity:0;letter-spacing:.6em}50%{opacity:1}100%{opacity:0;letter-spacing:.3em}}
.fj-hero img{animation:fjHeroIn 1.8s cubic-bezier(.2,.7,.2,1) .35s both, fjKen 18s ease-in-out 2.2s infinite alternate}
@keyframes fjHeroIn{from{opacity:0;transform:scale(1.12);filter:blur(6px)}to{opacity:1;transform:scale(1);filter:blur(0)}}
@keyframes fjKen{from{transform:scale(1)}to{transform:scale(1.05)}}
.fj-hero::after{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(105deg,transparent 35%,rgba(255,226,160,.28) 48%,rgba(255,255,255,.12) 52%,transparent 65%);transform:translateX(-100%);animation:fjShine 1.6s ease 1.6s, fjShine 1.6s ease 9s infinite}
@keyframes fjShine{to{transform:translateX(100%)}}
.fj-reveal{opacity:0;transform:translateY(40px);transition:opacity .9s ease,transform .9s cubic-bezier(.2,.7,.2,1)}
.fj-reveal.fj-in{opacity:1;transform:none}
@media (prefers-reduced-motion:reduce){.fj-curtain{display:none}.fj-hero img,.fj-hero::after{animation:none}.fj-reveal{opacity:1;transform:none;transition:none}}
</style>
@endpush

@push('scripts')
<script>
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    try { if (sessionStorage.getItem('fjCurtain')) throw 0; sessionStorage.setItem('fjCurtain', 1);
        var c = document.createElement('div'); c.className = 'fj-curtain';
        c.innerHTML = '<i></i><i></i><i></i><i></i><b>FUJIBA</b>';
        document.body.appendChild(c); setTimeout(function () { c.remove(); }, 1900);
    } catch (e) {}
    function reveal() {
        var main = document.getElementById('main'); if (!main || !('IntersectionObserver' in window)) return;
        var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('fj-in'); io.unobserve(e.target); } }); }, { threshold: .12 });
        var kids = main.querySelectorAll(':scope > *, :scope > * > section, :scope > div > div');
        [].forEach.call(kids, function (el, i) {
            if (el.querySelector('.fj-hero') || el.closest('.fj-hero') || el.offsetHeight < 40) return;
            if (el.getBoundingClientRect().top < window.innerHeight * .9) return;
            el.classList.add('fj-reveal'); io.observe(el);
        });
    }
    window.addEventListener('load', function () { setTimeout(reveal, 300); });
})();
</script>
@endpush

<x-shop::layouts>
    <x-slot:title>{{ $channel->home_seo['meta_title'] ?? config('app.name') }}</x-slot>

    @foreach ($customizations as $customization)
        @php ($data = $customization->options ?? [])

        @if (! empty($data['audience']) && $data['audience'] !== $audience)
            @continue
        @endif

        @if (! empty($data['images']))
            @php ($data['images'] = array_values(array_filter($data['images'], fn ($img) => empty($img['audience']) || $img['audience'] === $audience)))
        @endif

        @switch ($customization->type)
            @case ($customization::IMAGE_CAROUSEL)
                <x-shop::carousel :options="$data" />

                @if ($channelCode === 'default')
                    <x-shop::fujiba.catalog-book />
                @endif
                @break

            @case ($customization::STATIC_CONTENT)
                @if (! empty($data['css']))
                    @push('styles')<style>{{ $data['css'] }}</style>@endpush
                @endif
                @if (! empty($data['html']))
                    {!! $data['html'] !!}
                @endif
                @break

            @case ($customization::CATEGORY_CAROUSEL)
                <x-shop::categories.carousel
                    :title="$data['title'] ?? ''"
                    :src="route('shop.api.categories.index', $data['filters'] ?? [])"
                />
                @break

            @case ($customization::PRODUCT_CAROUSEL)
                <x-shop::products.carousel
                    :title="$data['title'] ?? ''"
                    :src="route('shop.api.products.index', $data['filters'] ?? [])"
                />
                @break

            @case ($customization::FOOTER_LINKS)
                {{-- Rendered by the footer layout component. --}}
                @break

            @case ($customization::SERVICES_CONTENT)
                {{-- Rendered by the services layout component. --}}
                @break

            @case ($customization::PRODUCT_GRID_FILTER)
                @include('shop::home.partials.product-grid-filter', ['customization' => $customization])
                @break
        @endswitch
    @endforeach

    @if (str_starts_with($channel->hostname ?? '', 'kimono.'))
        @include('shop::home.partials.kimono-faq')
    @endif
</x-shop::layouts>
