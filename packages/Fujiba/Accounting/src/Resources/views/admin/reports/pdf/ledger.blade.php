@extends('accounting::admin.reports.pdf._layout')

@section('title', __('accounting::app.reports.ledger'))
@section('period', $data['account']['code'] . ' ' . $data['account']['name_ja'] . ' / ' . $period['label'])

@section('body')
    <table class="r">
        <thead>
            <tr>
                <th>日付</th>
                <th>仕訳No</th>
                <th>摘要</th>
                <th class="num">借方</th>
                <th class="num">貸方</th>
                <th class="num">残高</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="5"><strong>期首残高</strong></td><td class="num"><strong>¥{{ number_format($data['opening']) }}</strong></td></tr>
            @foreach ($data['rows'] as $r)
                <tr>
                    <td>{{ $r['entry_date'] }}</td>
                    <td>{{ $r['entry_no'] }}</td>
                    <td>{{ $r['memo'] }}</td>
                    <td class="num">@if ($r['debit']) ¥{{ number_format($r['debit']) }} @endif</td>
                    <td class="num">@if ($r['credit']) ¥{{ number_format($r['credit']) }} @endif</td>
                    <td class="num">¥{{ number_format($r['running']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="3">期間合計</td>
                <td class="num">¥{{ number_format($data['period_debit']) }}</td>
                <td class="num">¥{{ number_format($data['period_credit']) }}</td>
                <td></td>
            </tr>
            <tr class="total"><td colspan="5">期末残高</td><td class="num">¥{{ number_format($data['closing']) }}</td></tr>
        </tbody>
    </table>
@endsection
