<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>{{ __('reorder::app.labels.title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: sans-serif; margin: 0; padding: 10mm; }
        .sheet { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6mm; }
        .label { border: 1px dashed #999; padding: 4mm; page-break-inside: avoid; text-align: center; }
        .label .sku { font-size: 11pt; font-weight: bold; margin-bottom: 2mm; }
        .label .qty { font-size: 9pt; color: #555; margin-top: 1mm; }
        .warn { background: #fff3cd; border: 1px solid #ffeeba; padding: 8px; margin-bottom: 10mm; font-size: 10pt; }
        @media print {
            .no-print { display: none; }
            .warn { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 10mm;">
        <button onclick="window.print()">{{ __('reorder::app.labels.print') }}</button>
    </div>

    @if (! empty($missing))
        <div class="warn">
            {{ __('reorder::app.labels.missing-barcode') }}: {{ implode(', ', $missing) }}
        </div>
    @endif

    <div class="sheet">
        @foreach ($labels as $label)
            <div class="label">
                <div class="sku">{{ $label['sku'] }}</div>
                {!! $label['svg'] !!}
                <div class="qty">Qty: {{ $label['qty'] }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>
