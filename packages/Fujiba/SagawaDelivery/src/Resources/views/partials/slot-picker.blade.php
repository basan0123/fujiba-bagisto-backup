{{--
  Delivery slot picker. Emits update:date / update:time via the input names
  configured below. Use in EC checkout (inside the shipping step) or POS
  (inside the sale-finalize dialog).

  Usage:
    @include('sagawa::partials.slot-picker', [
        'prefecture'    => $order->shipping_address->state ?? '',
        'dateName'      => 'delivery_date',
        'timeName'      => 'delivery_time',
        'initialDate'   => old('delivery_date'),
        'initialTime'   => old('delivery_time'),
    ])
--}}
@php
    $dateName    = $dateName    ?? 'delivery_date';
    $timeName    = $timeName    ?? 'delivery_time';
    $prefecture  = $prefecture  ?? '';
    $initialDate = $initialDate ?? '';
    $initialTime = $initialTime ?? '';
    $slots       = config('sagawa.time_slots', []);
    $endpoint    = route('sagawa.delivery_slots');
@endphp

<div
    x-data="sagawaSlotPicker({
        endpoint:   @js($endpoint),
        prefecture: @js($prefecture),
        dateName:   @js($dateName),
        timeName:   @js($timeName),
        initialDate: @js($initialDate),
        initialTime: @js($initialTime),
    })"
    x-init="init()"
    class="space-y-3"
>
    <div>
        <label class="block text-sm font-medium text-gray-700">お届け希望日</label>
        <select
            :name="dateName"
            x-model="selectedDate"
            :disabled="loading || !dates.length"
            class="mt-1 w-full rounded border-gray-300 text-sm disabled:bg-gray-100"
        >
            <option value="" x-show="loading">読み込み中...</option>
            <option value="" x-show="!loading && !dates.length">都道府県を選択してください</option>
            <template x-for="d in dates" :key="d">
                <option :value="d" x-text="formatDate(d)"></option>
            </template>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">お届け希望時間帯</label>
        <select
            :name="timeName"
            x-model="selectedTime"
            class="mt-1 w-full rounded border-gray-300 text-sm"
        >
            <option value="">指定なし</option>
            @foreach ($slots as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <p x-show="error" x-text="error" class="text-xs text-red-600"></p>
</div>

@once
    @push('scripts')
        <script>
            window.sagawaSlotPicker = function (opts) {
                return {
                    ...opts,
                    loading: false,
                    error: '',
                    dates: [],
                    selectedDate: opts.initialDate || '',
                    selectedTime: opts.initialTime || '',

                    init() {
                        if (this.prefecture) this.fetchDates();
                        this.$watch('prefecture', () => this.fetchDates());
                    },

                    async fetchDates() {
                        if (!this.prefecture) { this.dates = []; return; }

                        this.loading = true;
                        this.error   = '';

                        try {
                            const url = `${this.endpoint}?prefecture=${encodeURIComponent(this.prefecture)}`;
                            const res = await fetch(url, { headers: { Accept: 'application/json' } });
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            const data = await res.json();
                            this.dates = data.dates || [];

                            if (!this.dates.includes(this.selectedDate)) {
                                this.selectedDate = data.earliest || '';
                            }
                        } catch (e) {
                            this.error = '配送日候補の取得に失敗しました';
                        } finally {
                            this.loading = false;
                        }
                    },

                    formatDate(iso) {
                        const d = new Date(iso + 'T00:00:00+09:00');
                        const wd = ['日','月','火','水','木','金','土'][d.getDay()];
                        return `${d.getMonth()+1}/${d.getDate()} (${wd})`;
                    },
                };
            };
        </script>
    @endpush
@endonce
