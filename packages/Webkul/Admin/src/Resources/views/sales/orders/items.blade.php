<div class="flex flex-wrap gap-1.5">
    @php
        $restCount = max($order->items->count() - 3, 0);
        $coolOptMap = cache()->remember('cool_option_map', 300, function () {
            $map = [];
            $rows = \DB::table('attribute_options')
                ->join('attribute_option_translations', 'attribute_options.id', '=', 'attribute_option_translations.attribute_option_id')
                ->where('attribute_options.attribute_id', function ($q) { $q->select('id')->from('attributes')->where('code', 'Cool')->limit(1); })
                ->where('attribute_option_translations.locale', 'en')
                ->select('attribute_options.id', 'attribute_option_translations.label')->get();
            foreach ($rows as $r) {
                $l = strtolower(trim($r->label));
                if (str_contains($l, 'cool')) $map[$r->id] = 'cool';
                elseif (str_contains($l, 'freez') || str_contains($l, 'frozen')) $map[$r->id] = 'frozen';
                else $map[$r->id] = 'normal';
            }
            return $map;
        });
    @endphp

    @foreach ($order->items->take(3) as $item)
        @php
            $tempType = 'normal';
            if ($item->product) {
                try {
                    $cv = $item->product->Cool;
                    if ($cv) {
                        foreach (array_map('intval', explode(',', (string) $cv)) as $cid) {
                            $ct = $coolOptMap[$cid] ?? 'normal';
                            if ($ct === 'frozen') { $tempType = 'frozen'; break; }
                            if ($ct === 'cool') $tempType = 'cool';
                        }
                    }
                } catch (\Exception $e) {}
            }
        @endphp
        <div class="relative">
            <div class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded">
                @if ($item->product?->images->count() > 0)
                    <img
                        class="h-full w-full rounded"
                        src="{{ $item->product->base_image_url }}"
                    >

                    <span class="absolute bottom-px rounded-full {{ $item->qty_ordered > 1 ? 'bg-red-600 text-sm' : 'bg-darkPink text-xs' }} px-1.5 font-bold leading-normal text-white ltr:left-px rtl:right-px">
                        {{ $item->qty_ordered }}
                    </span>
                    @if($tempType !== 'normal')
                        <span class="absolute top-px text-xs ltr:right-px rtl:left-px">{{ $tempType === 'frozen' ? '⚠️❄️' : '❄️' }}</span>
                    @endif
                @else
                    <div class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded border border-dashed border-gray-300 dark:border-gray-800 dark:mix-blend-exclusion dark:invert">
                        <img src="{{ bagisto_asset('images/product-placeholders/front.svg') }}">

                        <p class="absolute bottom-1.5 w-full text-center text-[6px] font-semibold text-gray-400">
                            @lang('admin::app.sales.invoices.view.product-image')
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    @if ($restCount >= 1)
        <a href="{{ route('admin.sales.orders.view', $order->id) }}">
            <div class="flex h-[65px] w-[65px] items-center rounded bg-gray-50 dark:bg-gray-800">
                <p class="px-1.5 py-1.5 text-center text-xs font-bold text-gray-600 dark:text-gray-300">
                    @lang('admin::app.sales.orders.index.datagrid.product-count', ['count' => $restCount])
                </p>
            </div>
        </a>
    @endif
</div>