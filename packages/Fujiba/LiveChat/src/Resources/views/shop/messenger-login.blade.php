@extends('shop::layouts.master')

@section('page_title')
    {{ __('Messenger Login') }}
@stop

@section('content-wrapper')
<div class="auth-content" style="max-width:400px;margin:60px auto;text-align:center;padding:20px">
    @if(isset($error))
        <div style="background:#fee2e2;color:#991b1b;padding:16px;border-radius:8px;margin-bottom:20px">
            <strong>{{ __('Login Error') }}</strong><br>
            {{ $error }}
        </div>
        <a href="/" style="color:#3d94fd;text-decoration:underline">{{ __('Go to homepage') }}</a>
    @else
        <div style="background:#dcfce7;color:#166534;padding:16px;border-radius:8px">
            {{ __('Logging you in via Messenger...') }}
        </div>
    @endif
</div>
@endsection
