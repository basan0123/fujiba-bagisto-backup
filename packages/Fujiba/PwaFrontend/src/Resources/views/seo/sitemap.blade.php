<?= '<?xml version="1.0" encoding="UTF-8"?>'."\n" ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($urls as $u)
    <url>
        <loc>{{ $u['loc'] }}</loc>
@foreach ($u['alternates'] as $alt)
        <xhtml:link rel="alternate" hreflang="{{ $alt['hreflang'] }}" href="{{ $alt['href'] }}"/>
@endforeach
        <changefreq>{{ $u['changefreq'] }}</changefreq>
        <priority>{{ $u['priority'] }}</priority>
    </url>
@endforeach
</urlset>
