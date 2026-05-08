@extends('accounting::admin.reports.pdf._layout')

@section('title', __('accounting::app.reports.trial_balance'))
@section('period', $period['label'])

@section('body')
    @unless ($data['balanced'])
        <div class="warn">
            {{ __('accounting::app.reports.unbalanced_warn') }}
            (¥{{ number_format($data['total_debit']) }} / ¥{{ number_format($data['total_credit']) }})
        </div>
    @endunless

    <table class="r">
        <thead>
            <tr>
                <th>{{ __('accounting::app.reports.account') }}</th>
                <th>Type</th>
                <th class="num">{{ __('accounting::app.reports.debit') }}</th>
                <th class="num">{{ __('accounting::app.reports.credit') }}</th>
                <th class="num">{{ __('accounting::app.reports.balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['rows'] as $r)
                <tr>
                    <td>{{ $r['code'] }} {{ $r['name_ja'] }}</td>
                    <td>{{ $r['type'] }}</td>
                    <td class="num">¥{{ number_format($r['debit']) }}</td>
                    <td class="num">¥{{ number_format($r['credit']) }}</td>
                    <td class="num">¥{{ number_format($r['balance']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="2">{{ __('accounting::app.reports.totals') }}</td>
                <td class="num">¥{{ number_format($data['total_debit']) }}</td>
                <td class="num">¥{{ number_format($data['total_credit']) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
@endsection
