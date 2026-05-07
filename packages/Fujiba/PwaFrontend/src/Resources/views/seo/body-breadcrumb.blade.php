@php
    /**
     * BreadcrumbList JSON-LD.
     *
     * Fires inside the body via two view_render_event hooks:
     *   - bagisto.shop.products.view.before  (passes $product)
     *   - bagisto.shop.categories.view.banner_path.before (no data → re-fetch via slug)
     *
     * Both produce a Home › … › current-page chain.
     */
    $channel  = core()->getCurrentChannel();
    $hostname = trim((string) ($channel->hostname ?: request()->getHttpHost()), " \t\n\r/");
    if (! preg_match('#^https?://#i', $hostname)) {
        $hostname = request()->getScheme().'://'.$hostname;
    }
    $base = rtrim($hostname, '/');

    $items = [[
        '@type'    => 'ListItem',
        'position' => 1,
        'name'     => __('shop::app.components.layouts.header.mobile.nav-home'),
        'item'     => $base.'/',
    ]];

    if (isset($product) && $product) {
        $cat = $product->categories->first() ?? null;
        if ($cat) {
            $catSlug = optional($cat->translations->firstWhere('locale', app()->getLocale()))->slug
                       ?? optional($cat->translations->first())->slug;
            $catName = optional($cat->translations->firstWhere('locale', app()->getLocale()))->name
                       ?? optional($cat->translations->first())->name;
            if ($catSlug && $catName) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => count($items) + 1,
                    'name'     => $catName,
                    'item'     => $base.'/'.$catSlug,
                ];
            }
        }
        $items[] = [
            '@type'    => 'ListItem',
            'position' => count($items) + 1,
            'name'     => $product->name,
            'item'     => $base.'/'.$product->url_key,
        ];
    } else {
        $slug = trim(request()->path(), '/');
        if ($slug && $slug !== '/') {
            $cat = \Webkul\Category\Models\Category::query()
                ->whereHas('translations', fn ($q) => $q->where('slug', $slug))
                ->with('translations')->first();
            if ($cat) {
                $catName = optional($cat->translations->firstWhere('locale', app()->getLocale()))->name
                           ?? optional($cat->translations->first())->name;
                if ($catName) {
                    $items[] = [
                        '@type'    => 'ListItem',
                        'position' => count($items) + 1,
                        'name'     => $catName,
                        'item'     => $base.'/'.$slug,
                    ];
                }
            }
        }
    }

    if (count($items) < 2) return;

    $breadcrumb = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    ];
@endphp

<script type="application/ld+json">{!! json_encode($breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
