<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>適格請求書 {{ $invoice['incrementId'] ?? $invoice['id'] }}</title>
    <style>
        @page { margin: 24mm 16mm; }
        body { font-family: 'DejaVu Sans', 'IPAGothic', sans-serif; color: #1F2937; font-size: 11pt; line-height: 1.4; }
        h1 { font-size: 18pt; margin: 0 0 4pt; }
        .meta { font-size: 9pt; color: #6B7280; margin-bottom: 16pt; }
        .row { display: table; width: 100%; margin-bottom: 14pt; }
        .col { display: table-cell; vertical-align: top; width: 50%; }
        .label { font-size: 9pt; color: #6B7280; margin-bottom: 2pt; }
        .block { padding: 8pt 10pt; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 4pt; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 6pt; }
        table.items th, table.items td { border-bottom: 1px solid #E5E7EB; padding: 6pt 4pt; text-align: left; font-size: 10pt; }
        table.items th { background: #F3F4F6; font-weight: 700; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .sku { font-size: 8pt; color: #6B7280; }
        .totals { margin-top: 12pt; width: 60%; margin-left: 40%; }
        .totals td { padding: 4pt 6pt; font-size: 10pt; }
        .totals .grand { font-weight: 800; font-size: 12pt; border-top: 2px solid #1F2937; }
        .tax-table { width: 60%; margin-left: 40%; margin-top: 8pt; }
        .tax-table th, .tax-table td { padding: 3pt 6pt; font-size: 9pt; border-bottom: 1px solid #E5E7EB; }
        .reg { display: inline-block; padding: 2pt 6pt; background: #DBEAFE; color: #1E3A8A; font-weight: 700; border-radius: 3pt; font-size: 10pt; }
        .footer-note { margin-top: 24pt; font-size: 8pt; color: #6B7280; border-top: 1px solid #E5E7EB; padding-top: 6pt; }
    </style>
</head>
<body>
    <h1>適格請求書</h1>
    <div class="meta">
        Qualified Invoice (Tax Invoice) ·
        No. {{ $invoice['incrementId'] ?? $invoice['id'] }} ·
        {{ \Carbon\Carbon::parse($invoice['createdAt'])->format('Y-m-d') }}
        @if ($invoice['issuerRegistrationNumber'])
            · <span class="reg">登録番号: {{ $invoice['issuerRegistrationNumber'] }}</span>
        @endif
    </div>

    <div class="row">
        <div class="col">
            <div class="label">発行者</div>
            <div class="block">
                <strong>{{ $invoice['issuerName'] ?: '—' }}</strong><br>
                @if ($invoice['issuerAddress']) {{ $invoice['issuerAddress'] }}<br> @endif
                @if ($invoice['issuerPhone']) TEL: {{ $invoice['issuerPhone'] }} @endif
            </div>
        </div>
        <div class="col" style="padding-left: 8pt;">
            <div class="label">宛先</div>
            <div class="block">
                <strong>{{ $invoice['recipientName'] ?: '—' }}</strong><br>
                @if ($invoice['recipientEmail']) {{ $invoice['recipientEmail'] }}<br> @endif
                @if ($invoice['recipientPhone']) {{ $invoice['recipientPhone'] }}<br> @endif
                @if ($invoice['recipientAddress']) {{ $invoice['recipientAddress'] }} @endif
            </div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:50%">品目</th>
                <th class="num" style="width:10%">数量</th>
                <th class="num" style="width:15%">単価</th>
                <th class="num" style="width:10%">税率</th>
                <th class="num" style="width:15%">金額</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice['items'] as $item)
                <tr>
                    <td>
                        {{ $item['name'] }}<br>
                        <span class="sku">{{ $item['sku'] }}</span>
                    </td>
                    <td class="num">{{ $item['qty'] }}</td>
                    <td class="num">¥{{ number_format($item['price']) }}</td>
                    <td class="num">{{ number_format($item['taxPercent'], 0) }}%</td>
                    <td class="num">¥{{ number_format($item['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="tax-table">
        <thead>
            <tr>
                <th>税率</th>
                <th class="num">対象金額（税抜）</th>
                <th class="num">消費税額</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice['taxBreakdown'] as $tax)
                <tr>
                    <td>{{ number_format($tax['ratePercent'], 0) }}%</td>
                    <td class="num">¥{{ number_format($tax['baseAmount']) }}</td>
                    <td class="num">¥{{ number_format($tax['taxAmount']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>小計</td><td class="num">¥{{ number_format($invoice['subTotal']) }}</td></tr>
        @if ($invoice['discountAmount'] > 0)
            <tr><td>割引</td><td class="num">−¥{{ number_format($invoice['discountAmount']) }}</td></tr>
        @endif
        @if ($invoice['shippingAmount'] > 0)
            <tr><td>送料</td><td class="num">¥{{ number_format($invoice['shippingAmount']) }}</td></tr>
        @endif
        <tr><td>消費税合計</td><td class="num">¥{{ number_format($invoice['taxAmount']) }}</td></tr>
        <tr class="grand"><td>合計</td><td class="num">¥{{ number_format($invoice['grandTotal']) }}</td></tr>
    </table>

    <div class="footer-note">
        本書は消費税法第57条の4に定める適格請求書の要件を満たします。<br>
        This document satisfies the qualified invoice requirements per Japan Consumption Tax Act §57-4.
    </div>
</body>
</html>
