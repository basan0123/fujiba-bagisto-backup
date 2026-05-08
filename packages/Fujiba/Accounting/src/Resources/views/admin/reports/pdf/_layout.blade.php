<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1a1a1a; }
        h1 { font-size: 16pt; margin: 0 0 4mm; }
        .meta { font-size: 8pt; color: #666; margin-bottom: 6mm; }
        .meta td { padding: 1mm 4mm 1mm 0; }
        table.r { width: 100%; border-collapse: collapse; }
        table.r th, table.r td { padding: 1.5mm 2mm; border-bottom: 0.2pt solid #ccc; }
        table.r th { background: #f4f4f4; text-align: left; font-size: 8.5pt; text-transform: uppercase; color: #555; }
        .num { text-align: right; font-family: DejaVu Sans Mono, monospace; }
        .total { border-top: 0.5pt double #000; font-weight: bold; }
        .subtotal { font-style: italic; color: #555; }
        .group  { font-weight: bold; padding-top: 2mm; }
        .indent { padding-left: 6mm; }
        .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7pt; color: #999; text-align: center; }
        .warn { background: #fde8e8; color: #9b1c1c; padding: 2mm 3mm; margin-bottom: 3mm; border-radius: 2pt; }
    </style>
</head>
<body>
    <h1>@yield('title')</h1>
    <table class="meta">
        <tr>
            <td><strong>{{ __('accounting::app.reports.company_name') }}:</strong> {{ $companyName ?? config('app.name') }}</td>
            <td><strong>{{ __('accounting::app.reports.period') }}:</strong> @yield('period')</td>
            <td><strong>{{ __('accounting::app.reports.output_at') }}:</strong> {{ ($generatedAt ?? now())->toDateTimeString() }}</td>
        </tr>
    </table>

    @yield('body')

    <div class="footer">— Fujiba Accounting —</div>
</body>
</html>
