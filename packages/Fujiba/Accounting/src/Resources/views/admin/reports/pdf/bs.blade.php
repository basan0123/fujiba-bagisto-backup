@extends('accounting::admin.reports.pdf._layout')

@section('title', __('accounting::app.reports.bs'))
@section('period', __('accounting::app.reports.as_of') . ' ' . $data['as_of'])

@section('body')
    @unless ($data['balanced'])
        <div class="warn">
            {{ __('accounting::app.reports.unbalanced_warn') }}
            (¥{{ number_format($data['assets']['total']) }} ≠ ¥{{ number_format($data['total_liab_and_equity']) }})
        </div>
    @endunless

    <table style="width:100%; border-spacing:6mm 0;">
        <tr>
            <td valign="top" style="width:50%;">
                <table class="r">
                    <thead><tr><th colspan="2">{{ __('accounting::app.reports.bs_assets') }}</th></tr></thead>
                    <tbody>
                        @foreach (['current' => 'bs_current_assets', 'fixed' => 'bs_fixed_assets'] as $g => $label)
                            <tr><td class="group" colspan="2">{{ __('accounting::app.reports.' . $label) }}</td></tr>
                            @foreach ($data['assets'][$g]['rows'] as $r)
                                <tr><td class="indent">{{ $r['code'] }} {{ $r['name_ja'] }}</td><td class="num">¥{{ number_format($r['balance']) }}</td></tr>
                            @endforeach
                            <tr><td class="indent subtotal">subtotal</td><td class="num"><strong>¥{{ number_format($data['assets'][$g]['total']) }}</strong></td></tr>
                        @endforeach
                        <tr class="total"><td>{{ __('accounting::app.reports.bs_total_assets') }}</td><td class="num">¥{{ number_format($data['assets']['total']) }}</td></tr>
                    </tbody>
                </table>
            </td>
            <td valign="top" style="width:50%;">
                <table class="r">
                    <thead><tr><th colspan="2">{{ __('accounting::app.reports.bs_liabilities') }}</th></tr></thead>
                    <tbody>
                        @foreach (['current' => 'bs_current_liab', 'fixed' => 'bs_fixed_liab'] as $g => $label)
                            <tr><td class="group" colspan="2">{{ __('accounting::app.reports.' . $label) }}</td></tr>
                            @foreach ($data['liabilities'][$g]['rows'] as $r)
                                <tr><td class="indent">{{ $r['code'] }} {{ $r['name_ja'] }}</td><td class="num">¥{{ number_format($r['balance']) }}</td></tr>
                            @endforeach
                            <tr><td class="indent subtotal">subtotal</td><td class="num"><strong>¥{{ number_format($data['liabilities'][$g]['total']) }}</strong></td></tr>
                        @endforeach
                        <tr class="total"><td>{{ __('accounting::app.reports.bs_total_liab') }}</td><td class="num">¥{{ number_format($data['liabilities']['total']) }}</td></tr>
                    </tbody>
                </table>

                <table class="r" style="margin-top:4mm;">
                    <thead><tr><th colspan="2">{{ __('accounting::app.reports.bs_equity') }}</th></tr></thead>
                    <tbody>
                        @foreach (['capital' => 'bs_capital', 'reserve' => 'bs_retained'] as $g => $label)
                            <tr><td class="group" colspan="2">{{ __('accounting::app.reports.' . $label) }}</td></tr>
                            @foreach ($data['equity'][$g]['rows'] as $r)
                                <tr><td class="indent">{{ $r['code'] }} {{ $r['name_ja'] }}</td><td class="num">¥{{ number_format($r['balance']) }}</td></tr>
                            @endforeach
                            <tr><td class="indent subtotal">subtotal</td><td class="num"><strong>¥{{ number_format($data['equity'][$g]['total']) }}</strong></td></tr>
                        @endforeach
                        <tr><td class="indent">当期純利益(running)</td><td class="num">¥{{ number_format($data['equity']['retained_running']) }}</td></tr>
                        <tr class="total"><td>{{ __('accounting::app.reports.bs_total_equity') }}</td><td class="num">¥{{ number_format($data['equity']['total']) }}</td></tr>
                        <tr class="total" style="font-size:11pt;"><td>{{ __('accounting::app.reports.bs_total_liab_equity') }}</td><td class="num">¥{{ number_format($data['total_liab_and_equity']) }}</td></tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
@endsection
