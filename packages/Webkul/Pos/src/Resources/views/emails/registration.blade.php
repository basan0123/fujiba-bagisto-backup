@component('shop::emails.layout')
    <div style="margin-bottom: 34px;">
        <p style="font-weight: bold;font-size: 20px;color: #121A26;line-height: 24px;margin-bottom: 24px">
            @lang('pos::app.emails.dear', ['name' => $posUser->name]), 👋
        </p>

        <p style="font-size: 16px;color: #384860;line-height: 24px;">
            @lang('pos::app.emails.greeting')
        </p>
    </div>

    <p style="font-size: 16px;color: #384860;line-height: 24px;">
        @lang('pos::app.emails.registration.message')
    </p>
@endcomponent
