{{-- Page header. Pass $title, optional $subtitle, optional $back (url). --}}
@include('autoposter::admin._styles')

<div class="ap-header">
    <div class="ap-header-main">
        <div class="ap-logo">@include('autoposter::admin._icon', ['i' => 'facebook'])</div>
        <div>
            <h1 class="ap-title">{!! $title !!}</h1>
            @if (! empty($subtitle))
                <div class="ap-subtitle">{{ $subtitle }}</div>
            @endif
        </div>
    </div>

    @if (! empty($back))
        <a href="{{ $back }}" class="ap-btn ap-btn-secondary ap-btn-sm">
            @include('autoposter::admin._icon', ['i' => 'arrow-left']) {{ $backLabel ?? 'Back' }}
        </a>
    @endif
</div>
