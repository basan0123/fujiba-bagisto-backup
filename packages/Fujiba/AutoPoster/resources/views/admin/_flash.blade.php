{{-- Server-side flash messages for the redirect-with-session path used
     throughout this module. Success messages fade out on their own. --}}
@foreach ([
    'success' => ['ap-alert-success', 'check', true],
    'warning' => ['ap-alert-warn', 'alert', false],
    'error'   => ['ap-alert-danger', 'alert', false],
    'info'    => ['ap-alert-info', 'info', false],
] as $key => [$cls, $icon, $autohide])
    @if (session($key))
        <div class="ap-alert {{ $cls }}" role="status" @if ($autohide) data-autohide @endif>
            @include('autoposter::admin._icon', ['i' => $icon])
            <div class="ap-alert-body">{{ session($key) }}</div>
            <button type="button" class="ap-alert-close" aria-label="Dismiss">&times;</button>
        </div>
    @endif
@endforeach

{{-- $errors is injected by the web middleware group (ShareErrorsFromSession).
     Guarded so this partial is also safe to render outside that group. --}}
@if (isset($errors) && $errors->any())
    <div class="ap-alert ap-alert-danger" role="alert">
        @include('autoposter::admin._icon', ['i' => 'alert'])
        <div class="ap-alert-body">
            <ul style="margin: 0; padding-left: 18px; list-style: disc;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
