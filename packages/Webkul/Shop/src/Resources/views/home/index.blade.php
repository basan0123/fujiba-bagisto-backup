@php
    $channel     = core()->getCurrentChannel();
    $channelCode = $channel->code;
    $locale      = app()->getLocale();
@endphp

@push('meta')
    <meta name="title"       content="{{ $channel->home_seo['meta_title']       ?? '' }}" />
    <meta name="description" content="{{ $channel->home_seo['meta_description'] ?? '' }}" />
    <meta name="keywords"    content="{{ $channel->home_seo['meta_keywords']    ?? '' }}" />
@endpush

<x-shop::layouts>
    <x-slot:title>{{ $channel->home_seo['meta_title'] ?? config('app.name') }}</x-slot>

    @foreach ($customizations as $customization)
        @php ($data = $customization->options ?? [])

        @switch ($customization->type)
            @case ($customization::IMAGE_CAROUSEL)
                <x-shop::carousel :options="$data" />
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
</x-shop::layouts>
