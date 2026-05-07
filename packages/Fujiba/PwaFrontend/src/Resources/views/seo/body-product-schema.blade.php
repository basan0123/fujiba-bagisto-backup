@php
    if (! isset($product) || ! $product) return;

    $channel  = core()->getCurrentChannel();
    $hostname = trim((string) ($channel->hostname ?: request()->getHttpHost()), " \t\n\r/");
    if (! preg_match('#^https?://#i', $hostname)) {
        $hostname = request()->getScheme().'://'.$hostname;
    }
    $base     = rtrim($hostname, '/');
    $url      = $base.'/'.$product->url_key;
    $currency = core()->getCurrentCurrencyCode() ?: 'JPY';

    $priceVal = null;
    try {
        if (method_exists($product, 'getTypeInstance') && $product->getTypeInstance()) {
            $priceVal = $product->getTypeInstance()->getMinimalPrice();
        }
    } catch (\Throwable) {
        $priceVal = null;
    }
    if ($priceVal === null && isset($product->price)) {
        $priceVal = $product->price;
    }

    $images = [];
    foreach ($product->images ?? [] as $img) {
        if (! empty($img->url)) {
            $images[] = $img->url;
        } elseif (! empty($img->path)) {
            $images[] = \Storage::url($img->path);
        }
    }
    if (empty($images) && method_exists($product, 'getImageAttribute')) {
        $main = $product->image ?? null;
        if ($main) $images[] = $main;
    }

    $availability = $product->haveSufficientQuantity(1) ?? true
        ? 'https://schema.org/InStock'
        : 'https://schema.org/OutOfStock';

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $product->name,
        'sku'         => $product->sku,
        'url'         => $url,
        'description' => strip_tags((string) ($product->short_description ?? $product->description ?? '')),
    ];
    if (! empty($images)) {
        $schema['image'] = array_values(array_unique($images));
    }
    if ($priceVal !== null) {
        $schema['offers'] = [
            '@type'         => 'Offer',
            'url'           => $url,
            'priceCurrency' => $currency,
            'price'         => (string) $priceVal,
            'availability'  => $availability,
        ];
    }
@endphp

<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
