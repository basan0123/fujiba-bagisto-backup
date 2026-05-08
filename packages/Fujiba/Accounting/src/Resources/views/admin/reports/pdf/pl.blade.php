@extends('accounting::admin.reports.pdf._layout')

@section('title', __('accounting::app.reports.pl'))
@section('period', $period['label'])

@section('body')
    @php $s = $data['sections']; @endphp
    <table class="r">
        @foreach ([
            'sales' => 'pl_sales',
            'cogs'  => 'pl_cogs',
        ] as $key => $label)
            <tr><td class="group" colspan="2">{{ __('accounting::app.reports.' . $label) }}</td></tr>
            @foreach ($s[$key]['rows'] as $r)
                <tr><td class="indent">{{ $r['code'] }} {{ $r['name_ja'] }}</td><td class="num">¥{{ number_format($r['balance']) }}</td></tr>
            @endforeach
            <tr><td class="indent subtotal">subtotal</td><td class="num"><strong>¥{{ number_format($s[$key]['total']) }}</strong></td></tr>
        @endforeach
        <tr class="total"><td>{{ __('accounting::app.reports.pl_gross_profit') }}</td><td class="num">¥{{ number_format($s['gross_profit']['total']) }}</td></tr>

        <tr><td class="group" colspan="2">{{ __('accounting::app.reports.pl_sga') }}</td></tr>
        @foreach ($s['sga']['rows'] as $r)
            <tr><td class="indent">{{ $r['code'] }} {{ $r['name_ja'] }}</td><td class="num">¥{{ number_format($r['balance']) }}</td></tr>
        @endforeach
        <tr><td class="indent subtotal">subtotal</td><td class="num"><strong>¥{{ number_format($s['sga']['total']) }}</strong></td></tr>

        <tr class="total"><td>{{ __('accounting::app.reports.pl_operating') }}</td><td class="num">¥{{ number_format($s['operating']['total']) }}</td></tr>

        @foreach (['non_op_revenue' => 'pl_non_op_revenue', 'non_op_expense' => 'pl_non_op_expense'] as $key => $label)
            <tr><td class="group" colspan="2">{{ __('accounting::app.reports.' . $label) }}</td></tr>
            @foreach ($s[$key]['rows'] as $r)
                <tr><td class="indent">{{ $r['code'] }} {{ $r['name_ja'] }}</td><td class="num">¥{{ number_format($r['balance']) }}</td></tr>
            @endforeach
            <tr><td class="indent subtotal">subtotal</td><td class="num"><strong>¥{{ number_format($s[$key]['total']) }}</strong></td></tr>
        @endforeach

        <tr class="total"><td>{{ __('accounting::app.reports.pl_ordinary') }}</td><td class="num">¥{{ number_format($s['ordinary']['total']) }}</td></tr>

        @foreach (['special_revenue' => 'pl_special_revenue', 'special_expense' => 'pl_special_expense'] as $key => $label)
            <tr><td class="group" colspan="2">{{ __('accounting::app.reports.' . $label) }}</td></tr>
            @foreach ($s[$key]['rows'] as $r)
                <tr><td class="indent">{{ $r['code'] }} {{ $r['name_ja'] }}</td><td class="num">¥{{ number_format($r['balance']) }}</td></tr>
            @endforeach
            <tr><td class="indent subtotal">subtotal</td><td class="num"><strong>¥{{ number_format($s[$key]['total']) }}</strong></td></tr>
        @endforeach

        <tr class="total" style="font-size:11pt;"><td>{{ __('accounting::app.reports.pl_pretax') }}</td><td class="num">¥{{ number_format($s['pretax']['total']) }}</td></tr>
    </table>
@endsection
