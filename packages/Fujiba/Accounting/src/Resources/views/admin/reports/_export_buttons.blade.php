@php
    /** @var string $reportName */
    /** @var array $params */
    /** @var bool $allowPdf */
    $allowPdf = $allowPdf ?? true;
@endphp
<div class="flex items-center gap-2 print:hidden">
    @if ($allowPdf)
        <a href="{{ route('admin.accounting.reports.' . $reportName, array_merge($params, ['format' => 'pdf'])) }}"
           class="rounded-md border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300">
            {{ __('accounting::app.reports.export_pdf') }}
        </a>
    @endif
    <a href="{{ route('admin.accounting.reports.' . $reportName, array_merge($params, ['format' => 'csv'])) }}"
       class="rounded-md border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300">
        {{ __('accounting::app.reports.export_csv') }}
    </a>
</div>
