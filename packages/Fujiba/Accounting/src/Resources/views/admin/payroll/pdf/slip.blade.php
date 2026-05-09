<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1a1a1a; }
        h1 { font-size: 16pt; margin: 0 0 3mm; }
        .meta { font-size: 8pt; color: #555; margin-bottom: 6mm; }
        .meta td { padding: 1mm 6mm 1mm 0; }
        .blocks { width: 100%; border-spacing: 0; }
        .blocks > tbody > tr > td { vertical-align: top; padding: 0 4mm 0 0; }
        .blocks > tbody > tr > td:last-child { padding-right: 0; }
        h2 { font-size: 11pt; margin: 0 0 2mm; padding: 1.5mm 2mm; background: #f0f0f0; border-left: 3pt solid #555; }
        table.kv { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
        table.kv td { padding: 1mm 2mm; border-bottom: 0.2pt solid #ccc; font-size: 9.5pt; }
        table.kv td.k { color: #555; }
        table.kv td.v { text-align: right; font-family: DejaVu Sans Mono, monospace; }
        .total { border-top: 0.5pt double #000; font-weight: bold; font-size: 10.5pt; }
        .net   { background: #f0f7ff; padding: 4mm 4mm; margin-top: 4mm; }
        .net .label { font-size: 9pt; color: #555; }
        .net .amount { font-size: 18pt; font-family: DejaVu Sans Mono, monospace; font-weight: bold; }
        .stamp { margin-top: 12mm; border: 0.3pt dashed #aaa; padding: 6mm; text-align: center; color: #aaa; font-size: 9pt; }
        .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7pt; color: #999; text-align: center; }
    </style>
</head>
<body>
    <h1>給与明細書 — {{ $slip->year_month }}</h1>
    <table class="meta">
        <tr>
            <td><strong>会社名:</strong> {{ $companyName ?? config('app.name') }}</td>
            <td><strong>支給日:</strong> {{ optional($slip->pay_date)->toDateString() ?? '—' }}</td>
            <td><strong>出力:</strong> {{ ($generatedAt ?? now())->toDateTimeString() }}</td>
        </tr>
        <tr>
            <td><strong>従業員:</strong> {{ $slip->staff?->name_ja }} ({{ $slip->staff?->staff_code }})</td>
            <td><strong>雇用区分:</strong> {{ $slip->staff?->employment_type }}</td>
            <td><strong>源泉区分:</strong> {{ $slip->calc_meta['withholding_class'] ?? $slip->staff?->withholding_class ?? 'A' }}</td>
        </tr>
    </table>

    <table class="blocks">
        <tr>
            <td style="width: 50%;">
                <h2>支給</h2>
                <table class="kv">
                    <tr><td class="k">基本給</td>          <td class="v">¥{{ number_format((int) $slip->base_pay) }}</td></tr>
                    <tr><td class="k">残業手当</td>        <td class="v">¥{{ number_format((int) $slip->overtime_pay) }}</td></tr>
                    <tr><td class="k">深夜手当</td>        <td class="v">¥{{ number_format((int) $slip->night_pay) }}</td></tr>
                    <tr><td class="k">休日手当</td>        <td class="v">¥{{ number_format((int) $slip->holiday_pay) }}</td></tr>
                    <tr><td class="k">通勤手当</td>        <td class="v">¥{{ number_format((int) $slip->commute_allowance) }}</td></tr>
                    <tr><td class="k">その他手当</td>      <td class="v">¥{{ number_format((int) $slip->other_allowance) }}</td></tr>
                    <tr class="total"><td>支給合計</td>    <td class="v">¥{{ number_format((int) $slip->gross_pay) }}</td></tr>
                </table>
            </td>
            <td style="width: 50%;">
                <h2>控除</h2>
                <table class="kv">
                    <tr><td class="k">健康保険</td>        <td class="v">¥{{ number_format((int) $slip->health_insurance) }}</td></tr>
                    <tr><td class="k">厚生年金</td>        <td class="v">¥{{ number_format((int) $slip->pension) }}</td></tr>
                    <tr><td class="k">雇用保険</td>        <td class="v">¥{{ number_format((int) $slip->employment_insurance) }}</td></tr>
                    <tr><td class="k">所得税</td>          <td class="v">¥{{ number_format((int) $slip->income_tax) }}</td></tr>
                    <tr><td class="k">住民税</td>          <td class="v">¥{{ number_format((int) $slip->resident_tax) }}</td></tr>
                    <tr><td class="k">その他控除</td>      <td class="v">¥{{ number_format((int) $slip->other_deduction) }}</td></tr>
                    <tr class="total"><td>控除合計</td>    <td class="v">¥{{ number_format((int) $slip->total_deduction) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="net">
        <span class="label">差引支給額</span>
        <div class="amount" style="text-align:right;">¥{{ number_format((int) $slip->net_pay) }}</div>
    </div>

    <h2 style="margin-top:6mm;">勤怠</h2>
    <table class="kv">
        <tr><td class="k">通常勤務 (分)</td>  <td class="v">{{ number_format((int) $slip->regular_minutes) }}</td>
            <td class="k">残業 (分)</td>      <td class="v">{{ number_format((int) $slip->overtime_minutes) }}</td></tr>
        <tr><td class="k">深夜 (分)</td>      <td class="v">{{ number_format((int) $slip->night_minutes) }}</td>
            <td class="k">休日 (分)</td>      <td class="v">{{ number_format((int) $slip->holiday_minutes) }}</td></tr>
    </table>

    <div class="stamp">社判 / 受領印</div>
    <div class="footer">— Fujiba Accounting · {{ $slip->staff?->name_ja }} {{ $slip->year_month }} —</div>
</body>
</html>
